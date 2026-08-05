# Stage orders — new fields (item #237 jvT98E0M)

## Goal

Add fields to the `stage_orders` table so the frontend can, in the future, link back
to the original event a stage order came from, and so a stage order records when it was
computed, the stage start time, and whether it is official.

## Background (current state)

A `stage_orders` row records that a **source stage** was folded into a combined /
ranking stage. Rows are created idempotently in
`StageOrdersTable::getAllCreatingOne($srcStageId, $eventId, $stageId)`, which already
loads the source event to copy its description.

Existing columns: `id`, `event_id` (destination combined event), `stage_id`
(destination combined stage), `original_stage_id` (source stage), `stage_order`,
`description`, `created`, `modified`, `deleted`.

- GET `…/stageOrders/` returns `StageOrder::toArrayManagement()` — an explicit field
  list, currently `id`, `stage_order`, `description`, `created`.
- PATCH `…/stageOrders/{id}` currently allows editing `description` only
  (`_accessible` is `'*' => false` with `description => true`).
- `stages.start` (datetime) already exists (migration `AddStartToStages`).
- The OpenAPI spec (`typescript/v1api.yaml`/`.json`) and TS types
  (`typescript/domain/types/v1api/stageOrderManagement.ts`) are **generated** from
  controller-test runs → `openapi:download` → orval. They are not hand-edited.

## New columns on `stage_orders`

| Column | Type | Null | Default | Editable (PATCH) | Populated on creation from |
|---|---|---|---|---|---|
| `original_event_id` | string(36) | yes | null | **no** | source stage's `event_id` |
| `computed` | datetime | yes | null | **no** | `FrozenTime::now()` |
| `start` | datetime | yes | null | **yes** | source stage's `start` |
| `is_official` | boolean | no | `false` | **yes** | `false` |

Notes:

- `original_event_id` pairs with the existing `original_stage_id`; together they let the
  frontend build a link to the original event. Indexed, mirroring `original_stage_id`.
- `computed` is a **domain** timestamp (when the stage order was computed), kept distinct
  from the audit `created` column. Set once, never editable.
- `start` snapshots the source stage's `start` at creation but may be overridden by a
  manager via PATCH.
- New columns are nullable for existing rows (except `is_official`, default `false`).

## Immutability model

Immutability uses the entity's existing mechanism: `_accessible` stays `'*' => false`,
so immutable fields are never patchable. Values are set by **direct property assignment**
in `getAllCreatingOne()`.

- Immutable: `original_event_id`, `computed` (plus already-immutable `event_id`,
  `stage_id`, `original_stage_id`, `stage_order`).
- Editable: `description` (existing), `start`, `is_official` — added to `_accessible`,
  to `PatchStageOrdersBody`, and handled by the `edit()` controller action.

## Creation change (`StageOrdersTable::getAllCreatingOne`)

Replace the current "load the source event via `matching()`" step with loading the source
**stage** (with its event contained). One query yields everything needed:

- `original_event_id` ← source stage `event_id`
- `start` ← source stage `start`
- `description` ← source stage's event description (unchanged behaviour)
- `computed` ← `FrozenTime::now()`
- `is_official` ← `false`

Existing assignments (`stage_id`, `event_id`, `original_stage_id`, `stage_order`) stay.

## API exposure

`toArrayManagement()` gains: `original_event_id`, `original_stage_id`, `computed`,
`start`, `is_official` (in addition to the current `id`, `stage_order`, `description`,
`created`).

New columns are also added to the entity `_hidden` list (consistent with
`original_stage_id`), since serialized output flows through `toArrayManagement()` rather
than raw entity serialization.

## Generated OpenAPI / TS types

PHP + tests are updated in this repo. Regenerating `typescript/v1api.yaml`/`.json` and the
orval TS types is a **downstream step** that requires the running API (Docker):
run the controller tests (regenerates `swagger-openapi` JSON), then
`openapi:download` + orval build. This is called out in the implementation plan, not done
by hand-editing generated files.

## Migration

New migration `AddFieldsToStageOrders` (using `BaseMigration`, matching the newest
convention) adds the four columns to `stage_orders` and an index on `original_event_id`.

## Tests

- `StageOrdersFixture` — add the four new columns to the seed record.
- `StageOrdersTableTest` — assert `original_event_id`, `computed`, `start`, `is_official`
  are populated when a stage order is created via `getAllCreatingOne()`.
- `StageOrderTest` / `toArrayManagement()` — update expected output to include the new
  fields.
- `StageOrdersControllerTest` — GET output includes new fields; PATCH can update
  `start` and `is_official` (and cannot touch the immutable fields).

## Out of scope

- Frontend rendering of the original-event links (this only exposes the data).
- Backfilling `original_event_id` / `computed` / `start` for pre-existing rows (left null).
- Any change to `stages.start` (already exists).
