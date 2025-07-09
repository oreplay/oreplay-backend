# O Replay upload integration for results

## Authentication process

After the user adds the "**Token**", a new `GET` request must be made to `/api/v1/events/<Event ID>` to retrieve the list of available Stages. The variable Token will contain the **Event ID** in the first **36** characters. The remaining **6** characters of **Token** is the **secret** to be sent as Authorization header (e.g `'Authorization: Bearer bGb6Jt'` – see curl example below).

Example user interface. Fields **URL** and **Token** to be filled by the user:

The field **URL** should be editable, but prefilled by default to the URL https://www.oreplay.es This will allow the end user to change to a custom installation of the o-replay open source system

Example request with CURL:

```bash
curl --location --request GET 'https://www.oreplay.es/api/v1/events/79bad6e6-7c42-4317-958d-5c83c905b0ad?client=sportSoftware' --header 'Authorization: Bearer bGb6Jt'
```

This will return the following JSON when the request is processed successfully, and it will return a HTTP status code 200 (OK):

```json
{
   "event": {
       "id": "79bad6e6-7c42-4317-958d-5c83c905b0ad",
       "description": "Prueba radios ",
       "stages": [
           {
               "id": "19337eab-9a98-47a6-87e9-e2b1a14478f8",
               "description": "Prueba"
           },
           {
               "id": "e078b234-81b4-4d05-9db6-b73a453c667e",
               "description": "Prueba relevo 2"
           },
           {
               "id": "6c33c9ef-0751-43b9-9768-20fe2a4807e6",
               "description": "Test 2"
           },
           {
               "id": "995cdc24-66f9-4a64-bba6-90d6584475ac",
               "description": "Test"
           },
           {
               "id": "21dbd1f6-491e-46c1-9d48-7917d91a0f7f",
               "description": "adri CESA 25 larga 4 (\"2025-06-20T08:19:13.000+00:00\") CAD MAS tiene radios"
           },
           {
               "id": "31c722fe-ae13-4263-bc04-fcc6e8ad8a3a",
               "description": "adri CESA 25 larga 5 (\"2025-06-20T08:45:17.000+00:00\") radios+descarga antes del primer reset"
           }
       ]
   }
}
```

The field **description** could be displayed as soon as the response is completed with HTTP status code **200** (OK)

If the **Token** is invalid (wrong Event_id or wrong secret), the HTTP status code will be **401** (Unauthorized)

Other error codes like 400, 403, 404, 500, 502, 503 should not be used if there is no mayor error.
They COULD be handled with a generic message like "There is a problem with the request to the server (error code 400)"

The list of stages could be displayed in a dialog like this, to be selected by the user.
See below an example of a user interface to select the stage:
