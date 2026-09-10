# Breaking changes between `uploads` (v1) and `uploads/v2`

Both endpoints accept the same payload and run the same import. They differ only in what they send
back, and in one case in what an error *is*. This document is the contract difference, so a client
author does not have to diff two controllers to find it.

| | v1 | v2 |
|---|---|---|
| Route | `POST /api/v1/events/{eventID}/uploads/` | `POST /api/v1/events/{eventID}/uploads/v2/` |
| Controller | `UploadsController` | `UploadsV2Controller` |
| Import itself | its own copy of the class loop, inside the controller, **deliberately untouched** | `UploadProcessor` + `ClassImporter`, which is where new work happens |
| `upload_logs` row | written **once the upload is over**, as it always has been | written **before the loop and updated after every class** (`UploadProgressPublisher`), so an interrupted upload keeps the progress it reached |
| Status on failure | **always `202`** | **the real status** — `400`, `403`, `404`, `409`, `500` |
| `data` in the response | the whole saved entity graph | **always `[]`** |
| Response `meta` | `human` (rendered text with `<br>` and `<b>`) + `humanColor` (a hex colour) | `level` + `messages[]`, and `uploadType`. `human` and `humanColor` are **gone** |
| `?version=` query parameter | supported; below `402` the response is reshaped | **ignored** — always the modern shape |
| `?reprocess_all=` | supported | supported |
| Published in the OpenAPI spec | yes | **no** |

---

## 1. A failed upload no longer answers a success status

**v1** catches every exception and answers **`202 Accepted`**, putting the reason in
`meta.human` as `[ERROR - <code>] (<time>) <message>` with `meta.humanColor` red. A client that looks
only at the status code cannot tell a rejected upload from an accepted one. That is v1's contract and
it is not changing — the desktop client depends on it.

**v2** answers the real status. The rule, in `UploadsV2Controller::_errorStatus()`:

> use the exception's own code when it is in the range `400`-`599`, otherwise `500`.

The range is the filter rather than the exception class, deliberately: `RecordNotFoundException` is
not an `HttpException` but carries a genuine `404`, while a `PDOException` carries a SQLSTATE such as
`23000` that must never reach the wire as a status.

Observed today:

| Situation | v1 | v2 |
|---|---|---|
| No valid event token (`ForbiddenException`) | 202 | **403** |
| Malformed payload, e.g. `stages: []` | 202 | **400** |
| Start times uploaded when finish times exist | 202 | **400** |
| `raw_upload_id` that does not exist | 202 | **404** |
| Anything unexpected, including `PDOException` | 202 | **500** |
| An upload for the same stage is still importing | 202, and both imports run | **409**, and the second is refused |

**The body is unchanged.** `meta.updated`, `meta.human` and `meta.humanColor` are exactly as before
in both versions, so a client that already reads `meta.human` keeps working — it simply no longer
*has* to.

## 2. `data` is always empty

v1 returns every saved class, with its runners, results and splits hydrated, under `data`. v2 returns
`data: []` (`UploadMetrics::withoutSavedClasses()`, commit `a50e7c9`).

Measured on a 194-runner event: **peak memory −18 MB** (72 → 54) and **−2.63 s mean**. Retaining the
graph costs time as well as memory, and no consumer of it was ever found.

A client that reads `data` will find it empty on v2. Nothing else moved: the counts it might have
derived from `data` are in `meta.updated`.

## 3. `?version=` is ignored

v1 reads `?version=` and, when it is missing or below `402` (`UploadsController::NEW_VERSION`),
returns a reduced shape through `UploadMetrics::toArrayLegacy()`:

- `meta.updated` loses `courses`, `splits` and `runnerResults`, keeping only `classes` and `runners`
- `meta.timings` is removed entirely
- `meta.human` is replaced by a single line beginning
  `*** PLEASE UPDATE THE DESKTOP CLIENT TO THE LAST VERSION!!!`

v2 has no such branch and always returns the full shape. Sending `?version=` to v2 does nothing.

## 4. v2 is in the OpenAPI spec, with three gaps to know

Published 2026-08-24. `POST /api/v1/events/{eventID}/uploads/v2/` carries `operationId postListUploadsV2`,
the `reprocess_all` query parameter, a request body of `UploadPostData` and a `200` of `ResUploadedV2`. The
generated TypeScript is `ResUploadedV2`, `UploadedV2Meta` — whose `messages` is a real `UploadMessage[]` —
and `UploadMessageContext`. **The contract is therefore frozen from here**: changing the v2 envelope now
changes a published type.

The spec is generated from what the controller tests actually send and receive, so it inherits three limits
of that tooling. Each is covered below rather than in the spec:

1. **Only 2xx responses are recorded.** `409` and `403` are real and tested, and neither appears in the
   spec. See *One import at a time per stage* and §1.
2. **One operation per path**, because the reader merges files with a PHP array union and the first wins.
   The endpoint accepts `application/json` **and** `application/xml`, and the spec can only show one.
   The XML variant is §4a.
3. **`_c` markers are what name a schema.** Every captured request body sends `UploadPostData` /
   `UploadDataTransfer`, and the response carries `UploadedV2`, `UploadedV2Meta` and `UploadMessage`. These
   are generation metadata, not payload: a client ignores them, and the test helpers strip them before
   comparing. A captured request *without* them contributes its own inline schema and the body degenerates
   into a `oneOf`.

## 4a. The XML variant, not in the spec

The same URL accepts an IOF XML v3 document with `Content-Type: application/xml`, and answers the same
`ResUploadedV2` body. What differs is that XML carries no envelope, so what the JSON payload states must
come from the query string:

| parameter | meaning |
|---|---|
| `stage_id` | **required** — XML names no stage |
| `tz` | the time zone to read naive times in; otherwise `events.timezone` |
| `validate` | `false` skips XSD validation, which is on by default |
| `reprocess_all` | as for JSON: ignore stored hashes and re-import every class |

The upload type is detected from the document, including the `<!-- SplitTimeControls: … -->` comment
SportSoftware writes in radio exports.

## What is identical

Worth stating, because it is most of the surface: the request body, the authentication (a `Bearer`
event token), `?reprocess_all=`, every write the import performs, and the whole of `meta` apart from
the `version` reshaping in v1. Both controllers are kept deliberately in step — a change to one is
normally applied to the other in the same commit, and the exceptions are the four above.

## Testing

`UploadResponseTrait` carries the two assertions that encode this difference:

- `assertUploadOk()` — for both versions. It rejects the `[ERROR - ` marker in `meta.human`, which is
  the only way to detect a failed **v1** upload, since its 202 passes `assertResponseOk()`.
- `assertUploadRejected(int $status)` — v2 only, since v1 has no meaningful status to assert.

Two tests pin the difference itself:
`UploadsControllerTest::testAddNew_aRejectedUploadStillAnswers202` and
`UploadsV2ControllerTest::testAddNew_aRejectedUploadAnswersAnErrorStatus`.

## The v2 message format

Decided 2026-08-22. One envelope for every answer, success or failure:

```json
{ "meta": {
    "level": "warning",
    "uploadId": "f3414e0b-e605-494d-89f0-85d0bfbab2a0",
    "uploadType": "res_splits",
    "updated": { "classes": 3, "courses": 2, "runners": 45, "splits": 320, "runnerResults": 45 },
    "timings": { "processing": { ... }, "saving": { ... }, "total": 4.2 },
    "messages": [
      { "level": "warning", "code": "team_without_runners", "text": "Team without runners Ann's team",
        "context": { "team": "Ann's team" } }
    ]
  }, "data": [] }
```

**`code` is what a client acts on**; `text` is for a person and may be reworded at any time. Anything the
text names is repeated in `context`, so nothing has to be parsed back out of a sentence. RFC 9457
(`application/problem+json`) was considered and **rejected**: it describes a failed request, and most of what
an upload has to report — a duplicated runner, rows not saved, results without splits — arrives with 200, so
it would have covered the smaller half of the problem at the cost of a second shape.

**The two questions are separate.** The HTTP status says whether the request worked. `meta.level` says how
good the outcome was, and the interesting case is `200` with `level: error`: everything was stored, but two
entries merged into one runner and somebody's results are gone. A client that only reads the status will
miss that, which is exactly what `humanColor` red meant before.

`meta.uploadType` is null when the upload never got as far as reading the payload, which is the honest test
for "this answer came from the error path".

**`meta.uploadId` is the `upload_logs` row for this upload**, and every class pushed while it ran carries the
same id. A client can therefore tell which of its files produced a class it was pushed — retrospectively,
since the pushes arrive before the response does. There is deliberately no client-supplied idempotency key:
repeated uploads are already cheap because unchanged classes are skipped by hash, and overlapping ones are
refused by the stage lock, so a key would have bought only the ability to correlate live.

Alongside these, every object in the response carries a **`_c`** key naming its schema — `UploadedV2` on the
envelope, `UploadedV2Meta` on the meta, `UploadMessage` on each message. It is what the OpenAPI generator
reads to emit one named type instead of an anonymous shape, exactly as v1 does with `Uploaded`. A client
ignores it.

**Nothing is keyed on it and no client action depends on it.** A client that never learns the id — its POST timed out, the connection died — has lost only a label: the classes were still imported and still pushed, and the next GET or push carries the same truth.

| level | when |
|---|---|
| `info` | nothing to report, including "no class needed importing" |
| `warning` | something is odd but nothing was lost |
| `error` | the request failed, **or** it succeeded and lost data |

**Every message survives.** v1 shows data-loss warnings *instead of* the ordinary ones, so a real warning
can vanish behind another; v2 returns the lot, capped at ten of each kind, and `meta.level` is the highest
level present.

## The pushed class payload

**Both versions push.** v1 publishes too, since the desktop clients in the field upload through it and the feature
would otherwise never fire in production. The only difference a subscriber sees is `uploadId`: v1 sends **null**,
because it writes its `upload_logs` row after the import loop rather than before it, and moving that row is a change
to a frozen contract for a field only a client watching its own upload can use.

Each class is published to `stage/{stageId}/class/{classId}` as soon as it commits, so a stage of 20 classes
produces 20 messages during one upload. The participant objects are **the same shape `resultsByClass`
returns** — a client parses them with what it already has:

```json
{
  "uploadId": "f3414e0b-e605-494d-89f0-85d0bfbab2a0",
  "class": {"id": "9a2…", "short_name": "H21"},
  "runners": [
    {"id": "42", "full_name": "…", "bib": 101,
     "stage": {"position": 1, "time_seconds": 2841, "status_code": "0",
               "splits": [{"control": "31", "reading_time": 412}]}}
  ],
  "teams": []
}
```

**Every participant is sent, every time, but splits only for those whose punches this upload rewrote.**
One finisher crossing the line moves everybody's position, so a partial list would leave stale positions on
screen; splits are 84 % of a participant's bytes, so omitting the unchanged ones is what turns 147 kB into
about 27 kB before compression. It needs no filtering: the importer attaches splits to a result only when it
replaces them.

That makes the message a **patch, not a snapshot**, and a client has to read it as one:

| in `stage` | means |
|---|---|
| no `splits` key | this upload did not touch them — **keep the splits already held** |
| `"splits": []` | they were cleared, and the reader must clear them too |
| `"splits": […]` | authoritative, replacing whatever was held |

Absent and empty are one keystroke apart and mean opposite things, so a client reading `splits ?? []`
silently erases punches it was never told to erase. The distinction cannot be dropped: a radio upload that
legitimately removes punches would otherwise be indistinguishable from a participant nobody touched.

**A push is only ever applied on top of a full GET.** The API side of that is an invariant worth stating:
`resultsByClass` always returns whole objects, never a patch, so it is always a valid baseline. The client
side is a rule: fetch the class, then apply the stream to what came back.

**And re-seed on every reconnect**, because that is the one way a client loses messages. A subscriber is
served `nchan_subscriber_first_message newest`, so one that joins or rejoins mid-race receives the next
class and nothing earlier. A client that reconnects and simply resumes ends up showing the positions it
last saw with newer splits patched over them — internally consistent, plausible, and possibly minutes
stale, which is why nobody notices.

Detecting that needs nothing clever: websocket runs over TCP, so while a connection is up its messages
arrive in order and none goes missing quietly — the connection delivers or it breaks. **The client already
knows it disconnected**, and that is the trigger. One GET per reconnect is not the stampede per-class
push exists to prevent; that was every viewer polling, this is one client recovering.

The per-class version counter in `realtime-and-async-uploads.md` §5 would narrow this further — if a client
resumes with `Last-Event-ID` and nchan's buffer has already rolled past what it missed (10 messages, 1 h),
a version jumping 2 to 13 proves the replay was incomplete. That is an optimisation over re-seeding every
time, and **deliberately not built**: re-seeding is already correct, so the counter is worth adding only if
reconnects turn out to be frequent enough to measure.

## One import at a time per stage (v2 only)

A client that uploads every few seconds posts again before the previous file has finished importing, and
nothing serialised them: two imports load the same stored participants, neither sees the other's uncommitted
rows, and both create the same runner. The older one can also finish last and write its stale results over
the newer ones, so a leaderboard goes backwards.

v2 refuses the second with **409** rather than queueing it, because the client's next file is seconds away
and carries fresher data than anything a queue would hold. **v1 is unchanged**, deliberately: its contract
says every failure is a 202, and the deployed desktop clients hold that contract.

The lock lives in the cache, not in a column: `Cache::add()` is atomic on both the memcached and the redis
engines, and the entry expires by itself, so a request killed mid-import cannot block a stage for ever. Its
lifetime is **twice `UploadMetrics::MAX_PROCESSING_SECONDS`**, derived in `config/app.php` rather than
copied, so an upload can never outlive its own lock however that guard changes. It is released in a
`finally`, so the expiry is a backstop rather than the mechanism.
