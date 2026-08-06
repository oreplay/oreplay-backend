---
name: generate-orval-endpoint
description: Use when generating or regenerating the OpenAPI/Swagger spec and the orval TypeScript client for a CakePHP controller or endpoint in oreplay-backend — from its controller test. Triggers include "generate orval for X", "add endpoint to swagger", editing app_rest/phpunit-swagger.xml / typescript/v1api.yaml, and fixing orval oneOf/union, orphan types, missing query params, or type regressions.
---

# Generate an orval endpoint from its controller test

The OpenAPI spec is captured from a controller test, then orval turns it into the TS client.
Do this **one controller at a time**.

## Ground rule

Making an endpoint orval-ready is a **test/fixture job, not a production-code job**. In
production source, only: name an object (`_c` / `toChild`), or a short `SMELL:` comment.
Anything that changes runtime behaviour → stop and raise it. The spec is only as good as the
test's requests and assertions.

## Pipeline (this repo)

- **Phase 1** runs the controller tests with `app_rest/phpunit-swagger.xml` inside the PHP
  container. Each test writes `app_rest/swagger-openapi/<Class>.json` (+
  `swagger-openapi/component-schemas/<Class>.json`). `Swagger.identifyEntities` is on during
  these tests, and `skipNextRequestInSwagger()` drops the next request from capture.
- The **running dev app** (`docker-compose-dev.yml`, on `host.docker.internal`) serves
  `/api/v1/openapi/json` assembled from those JSON files via `SwaggerJsonController`.
- **Phase 2** (`docker-compose-typescript.yml` → `typescript/runOrval.sh`) curls that
  endpoint into `typescript/v1api.json`, converts it to `typescript/v1api.yaml`
  (`json-to-yaml.js`), then runs orval → `typescript/domain/types/v1api/` +
  `typescript/infrastructure/repositories/v1api.ts`.

Type names have the `Results` / `RestApi` / `Rankings` namespaces stripped (see the
`SWAGGER_NAMESPACE_TO_REMOVE*` env vars in `phpunit-swagger.xml`).

## Workflow

1. **Locate** the controller + its `*ControllerTest.php`, and read how the response is built
   (which entity / `toChild` / array).
2. **Register** the test in `app_rest/phpunit-swagger.xml`, inside `<testsuite name="Controllers">`:
   - **Results** plugin controllers are already covered by
     `<directory>plugins/Results/tests/TestCase/Controller/</directory>` — no edit needed.
   - **App-level, Rankings, and other plugins'** controllers need an explicit `<file>` entry.
     Add it to the matching group; keep the group together. Do **not** comment out other entries.
3. **Phase 1 — capture JSON** (isolate with `--filter`):
   ```
   docker exec oreplay-backend-nginx-1 bash -c "cd /var/www/cplatform/public/app_rest && \
     vendor/bin/phpunit --configuration phpunit-swagger.xml --filter <ControllerTestClass>"
   ```
   Writes `app_rest/swagger-openapi/<Class>.json` (+ `component-schemas/`).
4. **Phase 2 — build yaml + TS** (the dev app must be running so the curl resolves):
   ```
   docker compose -f docker-compose-typescript.yml up --abort-on-container-exit
   ```
   Rebuilds `typescript/v1api.yaml` and regenerates `typescript/domain/types/v1api/` +
   `typescript/infrastructure/repositories/`.
5. **Review** `git diff typescript/` against the checkpoints below before committing.

Iterate: fix in the test/fixtures, re-run phase 1, then phase 2.

## Review checkpoints (the hard part)

**No unions.** A response/property must be exactly one named type. Any `oneOf` /
`SomethingOneOf…` / "Any object" = a generic object returned without a name. Fix in PHP by
naming it (`toChild('Name', …)` / `_c`), preferring a real domain name.

**No orphan types.** A schema referenced by no path still emits a TS file. Common cause: two
requests on the same route+method with the **same top-level keys** but different item shapes —
the builder keeps the first and orphans the rest (no `oneOf`). To find the source test: read
the asserts (`_c` values), or binary-search with `--filter <ClassName>::<method>`. Pick the
canonical shape by which one **more tests** return; skip the minority variant with
`skipNextRequestInSwagger()`. If a consumer needs both shapes, give the variant its own route
(product decision — ask).

**Params: document ALL supported filters.** orval reads params only from captured request URLs.
Infer every supported param (the pagination trait, query-filter handling, the finders) and get
them in:
- Pack into the canonical test with values that keep the response identical: defaults
  (`page=1&limit=10`), real values that keep the same rows, empty for `LIKE`-based filters
  (`text=`). Empty values only work when the filter guards emptiness (`!empty()`, not `isset()`).
- A filter that changes the result → **duplicate the test, add it, fix the asserts**. Never omit
  a filter to avoid a duplicate. A filter that changes the response *shape* (not just rows) is
  the orphan/union case above.

**Regressions.** Diff `v1api.yaml`: mandatory `string` → `string|null`, `string` →
`string|number` (usually a number-like string returned as a number — fix the fixture), a type
widening, or a field disappearing. Warn and investigate; fix in PHP/fixtures, never by
hand-editing generated files.

**Orphan cleanup.** orval overwrites but never deletes. After phase 2, `git status typescript/`
and `rm` any generated type/repository referenced by no path (confirm it isn't used by another
endpoint first).

## Comments

No explanatory comments — rename/extract instead. Exception: when you skip a branch because the
endpoint returns two shapes, add a **smell comment** at that `if` in production code: under 150
chars, ≤2 lines, prefixed `REST smell:` / `Swagger smell:`, e.g.
```php
// REST smell: endpoint returns two response shapes based on $x; split into separate endpoints to fix.
```
Do not comment the skip in the test — `skipNextRequestInSwagger()` is self-describing.
