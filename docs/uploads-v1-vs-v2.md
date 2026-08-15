# Breaking changes between `uploads` (v1) and `uploads/v2`

Both endpoints accept the same payload and run the same import. They differ only in what they send
back, and in one case in what an error *is*. This document is the contract difference, so a client
author does not have to diff two controllers to find it.

| | v1 | v2 |
|---|---|---|
| Route | `POST /api/v1/events/{eventID}/uploads/` | `POST /api/v1/events/{eventID}/uploads/v2/` |
| Controller | `UploadsController` | `UploadsV2Controller` |
| Status on failure | **always `202`** | **the real status** — `400`, `403`, `404`, `500` |
| `data` in the response | the whole saved entity graph | **always `[]`** |
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

## 4. v2 is not in the OpenAPI spec

Every v2 test uses the capture-skipping `_getEndpoint()`, so nothing about v2 reaches
`typescript/v1api.yaml` and the generated orval client has no v2 operation. A practical consequence:
`reprocess_all` is documented on v1 only, though both accept it. Publishing v2 is a contract
decision and is deliberately left until the rest of the upload work is finished.

---

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
