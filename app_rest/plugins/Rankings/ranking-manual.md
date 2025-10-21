# Procedure to create ranking

We are still in development mode, there is a lot of management functionality missing.

- Upload race results (the **results event**) to a stage normally (as for any other event like if no ranking is used)
- Create a new event and stage to be used to display the ranking (the **ranking event**)
  - Create a new entry in the db with the configuration (include results event and stage IDs)
  - Run sql statements in `rankings` table. Customize parameters
  - To-do: create API and frontend to manage this
- Make an API request to generate ranking scores
  - POST to `/api/v1/rankings/{ranking_id}/events/{results_event_id}/stages/{results_stage_id}/compute` with the payload `{"classes":"all"}`
  - To-do: store time (next to points in the new event)
- Edit description for the stage order (to display correct race names in the ranking)
  - After computing the new race, a new entry in `stage_orders` table is created. We can update the name editing the field `description` with a sql statement.
  - We would need to clear cache in order the get the new updated description value
  - To-do: create API and frontend to manage this. Reuse same functionality to edit stage names in total results.
- Add organizers
  - Make a POST to `/api/v1/rankings/{ranking_id}/events/{ranking_event_id}/stages/{ranking_stage_id}/runnerResults/` with the payload `{"upload_type": "computable_org", "runner_id": "id", "stage_order": 3 }`
  - To-do: create UI to make this request
- Check runners in 2 different classes
  - To-do: API and UI to get them all and set them as not contributory
- Check clubs added twice (with different names)
