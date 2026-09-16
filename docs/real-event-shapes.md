# Real event shapes

What real orienteering data looks like, for sizing test fixtures. Every figure was **measured**: on the
private corpus in `a/` (147 IOF XML exports and 20 desktop-client JSON payloads, surveyed in August 2026)
or on production. Where something was not measured, it says so.

> The corpus holds real names and SportIdent card numbers. Use it to learn shapes, never copy from it:
> anything that becomes a test asset is anonymised first, like `tests/assets/Splits_CEEBO.xml`.

## At a glance

| Shape | Participants | Classes | Largest class | Splits per runner | Real example |
|---|---|---|---|---|---|
| Typical event | median **198** (p25 24, p75 647) | median **22** | median **27** | median **13** | 73 single-stage result lists |
| Big single stage | 850–**924** | 37–45 | 53–72 | 12–14, max 21 | `ParcialesCEO-Media`, `kam_250518_oz` |
| Multi-day totals | **1 404** | 37 | 118 | — | `Totales-final2-mom-2026` (4 stages) |
| Large start list | **2 374** | 48 | 99 | — | WMOC 2025 long final |
| Big relay | 390–400 teams, 1 160–1 265 runners | 15–36 | 308 | none exported | OS10 / OS12 results |
| Jukola 2025 | **1 473 teams, 5 892 runners** | **1** | 5 892 | none exported | `jukola/results_j2025_ve_iof.xml` |

**Not in the corpus:** no O-Ringen export, and no single stage of 1 000 or more participants (18 reach 500).
The large files point the same way, though: at 1 400–2 400 participants the class count stays under 50, and
the growth goes into class size.

## Individual events

- **Classes grow, the class list does not.** Events sit at 37–48 classes whether they have 900 or 2 400
  people. On the biggest single stage the median class holds 19 runners; across the large files the largest
  class holds 53–118.
- **13–14 splits per runner** on a normal download, up to ~21. Score/rogaine runs far longer: SiTiming
  averages ~46 splits per runner (5 592 over 121).
- **Missing splits are normal**: a median of 9.2 % of splits per file. An organiser can validate a runner by
  hand, so a result with status `OK` **and a position** may still carry a `Missing` split with no time.
- **Totals exports** carry one `Result` per stage per person. Their splits are the last stage's and are
  ignored.
- **Duplicates exist in the wild**: `19totales/…-m14only-DuplicatedRunner.xml` is a cut-down with two entries
  for one person.

## Relays

| Event | Teams | Runners | Legs | Classes | Course variants | Splits |
|---|---|---|---|---|---|---|
| Jukola 2025 | 1 473 | 5 892 | 4 | **1** | 64 (`V402`, `V406`…) | 0 |
| OS10 relay | 396 | 1 265 | 4 | 15 | 61 (`AAAA`, `BBBB`…) | 0 |
| OS12 relay | 400 | 1 200 | 3 | 36 | 414 (`#5 BB`, `#9 CC`…) | 0 |
| OS12 provisional | 257 | 1 028 | 4 | 17 | 452 (`#10 aBaA`…) | 0 |
| OS12 partial (JSON) | 291 | 1 107 | 4 | 18 | 9 | 21 172 |
| Sprint relay (kam) | 85 | 339 | 4 | 2 | 339 (`201.1`, `201.2`…) | 6 508 |
| Regional relay (CV) | 53–54 | 212–216 | 4 | 6 | 54 (`#7 aaB`…) | 0 |

- **3–4 legs, 3–4 runners per team.** OS10 averages 3.2, so incomplete teams occur.
- **Only 6 of 45 relay result files carry splits.** The kam sprint relay is the one real relay with splits at
  scale, at ~19 per runner.
- **Jukola puts every team in one class.** The whole file is a single `ClassResult`, which is the worst case
  for anything that holds a class in memory or matches runners within a class.

## Courses and variants

- **No export carries the control sequence.** Across every XML and JSON file, `<Course>` only holds `Name`,
  `Id`, `Length`, `Climb` and `NumberOfControls`. Control order has to be inferred from the punches.
- **Individual events get one course per class** (45 courses for 45 classes, 42 for 42), or several classes
  share one: CEEBO's 19 classes run 9 courses. OE sends no per-runner course, so individual forking cannot
  be seen.
- **Relays carry a course per runner, and that course is the variant.** `#14 aBaB` reads as variant 14, with
  branch a/B at each forking point. Some events give every runner a unique variant (kam: 339 for 339).
- **`oe_key` identifies the course family, not the variant**: one key covers 132 variants in the OS12
  provisional results. `short_name` is the variant.
- Sampled production payloads from OE and MeOS: `classes[].course` always present, `runners[].course` never.

## Splits, radios and statuses

| Result status | Count | | Split status | Count |
|---|---|---|---|---|
| `OK` | 60 012 | | `Missing` | 43 218 |
| `NotCompeting` | 3 769 | | `Additional` | 7 048 |
| `DidNotFinish` | 3 405 | | | |
| `DidNotStart` | 2 893 | | | |
| `MissingPunch` | 2 858 | | | |
| `Inactive` | 1 960 | | | |
| `OverTime` / `Disqualified` / `Finished` | 191 / 138 / 94 | | | |

- **Radio exports list every radio control**, including ones not reached yet: 65 % of radio splits were
  `Missing` in one mid-race export of 872 runners.
- **Radio batches are cumulative.** One mid-race export had 156 results with 2 splits, 34 with 4 and 43 with
  6, and consecutive exports changed **30–36 %** of runners (263 of 871, 318 of 885).
- `Inactive` means *out on the course* in SportSoftware, not *did not start*.

## Size and cost

- **Payload**: ~4.5 kB per runner. A runner with splits is 4 565 B, without them 720 B, and one split 223 B.
  A 19-class event is ~793 KB, a 10-runner class 35 KB, a 32-runner class 147 KB.
- **Files**: the largest XML in the corpus is Jukola's results at 5 534 KB. Encodings are 103 UTF-8, 31
  windows-1252 and 7 undeclared, and one file is malformed.
- **Import memory**: the entity graph holds ~18 kB per runner plus ~3.7 kB per split. A 2 000-runner class
  takes ~12 s and ~81 MB. A 6 000-runner class takes 37 s to match and peaks at ~209 MB, which is over both
  the 128 MB FPM `memory_limit` and the 200 MB container. That is why XML uploads refuse an average above
  2 000 competitors per class (`IofUpload::MAX_AVERAGE_ENTRIES_PER_CLASS`) and a body above 20 MB.
- **Vendors**: SportSoftware OE 82 files, OS 37, MeOS 5, QuickEvent 4, Mystaltallennus 2 (Jukola), OEScore
  2, SiTiming 2, SI-Droid 1.

## Production

- `splits`: 5 155 102 rows and 8.5 GB, 76 % of it indexes; 50.2 % of the rows are soft-deleted.
- Stage types: Classic 600 · Score 79 · Overall 50 · Relay 50 · One Man Relay 7. Mass start, chase, Trail-O
  and raid: none.
- Uploads so far: splits 168 984 · finish times 19 532 · radios 18 779 · total points 5 519 · start lists
  2 632 · total times 1 664 · entry lists 106.

## Test shapes to build from this

| Shape | Build | What it exercises |
|---|---|---|
| Typical | 200 runners, 22 classes, 13 splits each, ~9 % `Missing` | the everyday path |
| Big stage | 925 runners, 45 classes, largest 60, 14 splits | normal load |
| 1 500 runners *(extrapolated)* | ~48 classes, largest ~120, 14 splits | growth by class size, not class count |
| Radio batch | 880 runners, 1–6 cumulative splits, most still `Missing` | intermediates replacing, not appending |
| Big relay | 400 teams × 3 legs, ~400 variants, no splits | a course per runner |
| Jukola | 1 473 teams × 4 legs in **one** class, 64 variants | one-class memory and matching |
| Hand-validated runner | status `OK`, a position, one `Missing` split | splits without a time must survive |
