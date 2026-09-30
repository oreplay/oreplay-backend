# OpenAPI review: Events and Stages endpoints

Review of `typescript/v1api.yaml` on `main` at `9bcd4a5` for the endpoints served by `EventsController` and
`StagesController` (`app_rest/plugins/Results/src/Controller/`). Checked against the controllers, table finders,
entities and the controller tests that generate the spec.

**Scope**

| Path | Operations |
|---|---|
| `/api/v1/events/` | GET (list), POST |
| `/api/v1/events/{eventID}` | GET, PATCH, DELETE |
| `/api/v1/events/{eventID}/stages/` | GET (list), POST |
| `/api/v1/events/{eventID}/stages/{stageID}` | GET, PATCH, DELETE |

Other routes under `/events/…` (tokens, rawUploads, uploads, results, classes, clubs, stageOrders, stats) were not reviewed.

**Summary:** paths, operations, response wrappers (`PaginatedEvent`, `ResEvent`, `ArrayStage`, `ResStage`), the
DELETE `204` responses and all request bodies are now correct. What's left: no auth on the protected operations,
missing list filters, two unnamed event bodies, and a few minor issues.

## Already fixed on `main`

- **Request bodies declare every accepted field.** Event POST and PATCH include `scope`, `location`, `country_code`,
  `website`, `picture`, `timezone` and `organizer_id`. Both stage bodies include `start`.
- **Booleans are typed as `boolean`.** This covers `is_hidden` in both event bodies and `state_end` in the stage PATCH.
- **Stage POST has its own body.** `PostStagesBody` no longer advertises `state_end`, which `addNew` ignores.
  Its old generated file (`postListStagesBody.ts`) was renamed, so it isn't left behind as an orphan.
- **`Stage` no longer hides `base_date`, `base_time`, `server_offset` or `utc_value`.** This is safe: migration
  `20260817100000_DropDeadStageTimeColumns` drops those columns, so they can't leak into responses.

## Worth fixing

### 1. No `security` on any event or stage operation

POST, PATCH and DELETE on both resources require a bearer token, but the spec shows them as public.

- **Cause:** `SwaggerTestCase::getSecurity()` (`app_rest/vendor/freefri/cake-rest-api/src/Lib/Swagger/SwaggerTestCase.php:355`)
  decides per controller from `isPublicController()`. Both controllers return `true`, since GET is public.
- **Fix:** a library change, deciding per request (e.g. from whether an `Authorization` header was sent). The tests can't fix it.

### 2. Missing filters on `GET /events/`

`EventsTable::findPaginatedEvents` calls `handleTimeFilter` on `initial_date` and `final_date`. That supports
`gt`, `gte`, `lt` and `lte` for each field. The spec only has `initial_date:lte` and `final_date:gte`.

- **Missing:** `initial_date:gt`, `initial_date:gte`, `initial_date:lt`, `final_date:gt`, `final_date:lte`, `final_date:lt`.
- **`when`:** typed as a free string, but only `today`, `past` and `future` are accepted. Anything else returns 400.

### 3. Event request bodies are unnamed

POST and PATCH on events send no `_c`, so their bodies become inline "Generic object when: …" schemas. orval then
names them after the operations (`postListEventsBody.ts`, `patchEventsBody.ts`). The stage tests already set `_c`
(`PostStagesBody` / `PatchStagesBody`). Name the event bodies the same way, e.g. `PostEventsBody` / `PatchEventsBody`.

## Smaller issues

### 4. `GET /events/{eventID}` has an undocumented second response shape

With a desktop-client event token it returns `{ event: { id, description, stages: [{ id, description }] } }` instead of
`{ data: Event }`. Skipping that request in the test (`skipNextRequestInSwagger`) is correct. However, the
`if ($isDesktopClientAuthenticated)` branch in `EventsController::getData` is missing the `REST smell:` comment our
convention asks for.

### 5. One shared schema for different payloads

- **`Stage`:** `StagesController::getList` doesn't load `StageTypes`, so list items never include `stage_type`. The
  schema is still valid because `stage_type` is optional, but clients shouldn't expect it from the list.
- **`Event`:** list items include only `organizer`. The single-event GET also includes `federation` and `stages`.

### 6. Placeholder date in `Event.created`

The example is `'****-**-*****:**:**.***+**:**'`, which appears to come from date-masking in the tests. It's cosmetic,
but it is what the docs display.

### 7. Pagination links aren't named

`PaginationLinks.self`, `next` and `prev` are inline generic objects. That produces three types (`paginationLinksSelf.ts`,
`paginationLinksNext.ts`, `paginationLinksPrev.ts`) that all duplicate `LinkHref`. This comes from shared library
behaviour, not from the events endpoints.

### 8. Only success responses are documented

None of these operations list 400, 401, 403 or 404, although the tests cover those cases (validation errors, bad token,
another user's event). This applies to the whole generated spec.

## Suggested fixes

| Items | Where | How |
|---|---|---|
| 2, 3 | `EventsControllerTest` | Add the query params and `_c` names. Then rerun both generation phases (PHPUnit with `phpunit-swagger.xml`, then `docker-compose-typescript.yml`). |
| 4 | `EventsController::getData` | Add the one-line `REST smell:` comment. |
| 1, 7, 8 | `freefri/cake-rest-api` | Library changes. |
