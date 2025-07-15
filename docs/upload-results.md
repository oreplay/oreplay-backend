# O Replay upload integration for results

## Authentication process

After the user adds the "**Token**", a new `GET` request must be made to `/api/v1/events/<Event ID>` to retrieve the list of available Stages. The variable Token will contain the **Event ID** in the first **36** characters. The remaining **6** characters of **Token** is the **secret** to be sent as Authorization header (e.g `'Authorization: Bearer bGb6Jt'` – see curl example below).

Example user interface. Fields **URL** and **Token** to be filled by the user:

![Example user interface](https://github.com/oreplay/oreplay-backend/blob/main/docs/assets/token-input.png?raw=true)

The field **URL** should be editable, but prefilled by default to the URL https://www.oreplay.es This will allow the end user to change to a custom installation of the o-replay open source system

Example of **GET Events request** with CURL:

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
               "description": "Middle distance"
           },
           {
               "id": "e078b234-81b4-4d05-9db6-b73a453c667e",
               "description": "Sprint"
           },
           {
               "id": "6c33c9ef-0751-43b9-9768-20fe2a4807e6",
               "description": "Long distance"
           }
       ]
   }
}
```

The field **description** could be displayed as soon as the response is completed with HTTP status code **200** (OK)

If the **Token** is invalid (wrong Event_id or wrong secret), the HTTP status code will be **401** (Unauthorized)

Other error codes like `400`, `403`, `404`, `500`, `502`, `503` should not be used if there is no mayor error.
They COULD be handled with a generic message like "There is a problem with the request to the server (error code 400)"

The list of stages could be displayed in a dialog like this, to be selected by the user.
See below an example of a user interface to select the stage:

![Example stage selector](https://github.com/oreplay/oreplay-backend/blob/main/docs/assets/stage-selector.png?raw=true)

## Upload process

The start lists, or results are uploaded to using a `POST` request to `/api/v1/events/<Event ID>/uploads`

`POST` request to `https://www.oreplay.es/api/v1/events/79bad6e6-7c42-4317-958d-5c83c905b0ad/uploads?version=501` with the same **Authorization** header as the GET Events request.

The parameter **version** MUST be initially 501. This is the version of the upload schema.

Example request body. This is example is a draft to give a general illustration of the schema, not a proper API definition:

```json
{
    "configuration":{
        "source_vendor":"sportSoftware",
        "source":"OE12",
        "source_version":"12.2",
        "contents":"ResultList",
        "_values_on_contents_":"StartList | ResultList",
        "results_type":"Mixed",
        "utf":true
    },
    "event":{
        "id":"8f3b542c-23b9-4790-a113-b83d476c0ad9",
        "description":"Demo - 5 days of Italy 2014",
        "stages":[
            {
                "id":"8f45d409-72bc-4cdc-96e9-0a2c4504d964",
                "order_number":1,
                "description":"Test stage Long distance",
                "base_date":"2025-07-09",
                "base_time":"10:30:00.000+01:00",
                "controls":[
                    {
                        "station":"31"
                    },
                    {
                        "station":"100"
                    }
                ],
                "classes":[
                    {
                        "id":"",
                        "uuid":"",
                        "oe_key":"10",
                        "short_name":"ME",
                        "long_name":"M Elite",
                        "course":{
                            "id":"",
                            "uuid":"",
                            "distance":"5660.0",
                            "climb":"280.0",
                            "controls":22,
                            "oe_key":"26",
                            "short_name":"ME"
                        },
                        "runners":[
                            {
                                "id":"",
                                "uuid":"",
                                "sicard":"8011750",
                                "sicard_alt":"",
                                "license":"",
                                "first_name":"Francisco",
                                "last_name":"One Runner Downloaded",
                                "bib_number":"255",
                                "sex":"M",
                                "country":"Spain",
                                "region":"Madrid",
                                "is_nc":false,
                                "runner_results":[
                                    {
                                        "id":"",
                                        "position":1,
                                        "start_time":"2024-09-29T11:00:00.000",
                                        "finish_time":"2024-09-29T12:26:54.000",
                                        "time_seconds":5214,
                                        "status_code":"0",
                                        "time_behind":0,
                                        "time_neutralization":0,
                                        "time_adjusted":5214,
                                        "time_penalty":0,
                                        "time_bonus":0,
                                        "points_final":0,
                                        "points_adjusted":0,
                                        "points_penalty":0,
                                        "points_bonus":0,
                                        "splits":[
                                            {
                                                "station":"31",
                                                "points":0,
                                                "reading_time":"2024-01-28T10:15:05.000",
                                                "order_number":1,
                                                "is_intermediate":false
                                            },
                                            {
                                                "station":"100",
                                                "points":0,
                                                "reading_time":"2024-01-28T10:18:37.000",
                                                "order_number":2,
                                                "is_intermediate":false
                                            }
                                        ],
                                        "result_type":{
                                            "id":"e4ddfa9d-3347-47e4-9d32-c6c119aeac0e",
                                            "description":"Stage"
                                        }
                                    }
                                ],
                                "club":{
                                    "id":"",
                                    "uuid":"",
                                    "oe_key":"24738",
                                    "short_name":"BRIGHTNET",
                                    "long_name":"BRIGHTNET"
                                }
                            },
                            {
                                "id":"",
                                "uuid":"",
                                "sicard":"889818",
                                "sicard_alt":"889818",
                                "first_name":"Carlos",
                                "last_name":"One Runner Not Started Yet",
                                "bib_number":"359",
                                "sex":"M",
                                "country":"Spain",
                                "region":"Madrid",
                                "is_nc":false,
                                "runner_results":[
                                    {
                                        "id":"",
                                        "stage_order":1,
                                        "start_time":"2014-07-06T11:09:14.523+01:00",
                                        "status_code":"0",
                                        "leg_number":1,
                                        "result_type":{
                                            "id":"e4ddfa9d-3347-47e4-9d32-c6c119aeac0e",
                                            "description":"Stage"
                                        }
                                    }
                                ],
                                "club":{
                                    "id":"",
                                    "uuid":"",
                                    "oe_key":"24738",
                                    "short_name":"BRIGHTNET",
                                    "long_name":"BRIGHTNET"
                                }
                            },
                            {
                                "id":"",
                                "uuid":"",
                                "sicard":"8000001",
                                "sicard_alt":"",
                                "sex":"M",
                                "first_name":"Javier",
                                "last_name":"One Runner With Intermediates",
                                "bib_number":"1",
                                "country":"Spain",
                                "region":"Madrid",
                                "is_nc":false,
                                "runner_results":[
                                    {
                                        "id":"",
                                        "start_time":"2024-01-16T10:30:00.000+01:00",
                                        "status_code":"0",
                                        "time_neutralization":0,
                                        "time_adjusted":0,
                                        "time_penalty":0,
                                        "time_bonus":0,
                                        "leg_number":1,
                                        "splits":[
                                            {
                                                "station":"31",
                                                "points":0,
                                                "reading_time":"2024-01-16T10:56:47.000+01:00",
                                                "order_number":1,
                                                "is_intermediate":true
                                            }
                                        ],
                                        "result_type":{
                                            "id":"e4ddfa9d-3347-47e4-9d32-c6c119aeac0e",
                                            "description":"Stage"
                                        }
                                    }
                                ],
                                "club":{
                                    "id":"",
                                    "uuid":"",
                                    "oe_key":"1",
                                    "short_name":"A Coru\u00f1a LICEO",
                                    "long_name":"A Coru\u00f1a LICEO"
                                }
                            }
                        ]
                    },
                    {
                        "id":"",
                        "uuid":"",
                        "oe_key":"20",
                        "short_name":"Relay",
                        "long_name":"Relay with 1 team or many, with or without splits",
                        "course":{
                            "id":"",
                            "uuid":"",
                            "distance":"4710.0",
                            "climb":"230.0",
                            "controls":19,
                            "oe_key":"30",
                            "short_name":"WE\/M20"
                        },
                        "teams":[
                            {
                                "id":"",
                                "uuid":"",
                                "legs":3,
                                "bib_number":"1001",
                                "bib_alt":"",
                                "team_name":"CMA-SENIOR FEM-01",
                                "club":{
                                    "id":"",
                                    "uuid":"",
                                    "city":"",
                                    "oe_key":"12",
                                    "short_name":"Galicia",
                                    "long_name":"Galicia"
                                },
                                "team_results":[
                                    {
                                        "id":"",
                                        "position":1,
                                        "start_time":"2001-01-01T10:35:00.000+01:00",
                                        "time_seconds":3600,
                                        "status_code":"0",
                                        "time_behind":0,
                                        "splits":[

                                        ],
                                        "result_type":{
                                            "id":"e4ddfa9d-3347-47e4-9d32-c6c119aeac0e",
                                            "description":"Stage"
                                        }
                                    }
                                ],
                                "runners":[
                                    {
                                        "id":"",
                                        "uuid":"",
                                        "sicard":"8186666",
                                        "sex":"F",
                                        "first_name":"Mar\u00eda",
                                        "last_name":"Prado",
                                        "db_id":"6208",
                                        "iof_id":"",
                                        "bib_number":"1001-1",
                                        "sicard_alt":"",
                                        "runner_results":[
                                            {
                                                "id":"",
                                                "start_time":"2001-01-01T10:35:00.000+01:00",
                                                "finish_time":"2001-01-01T11:04:04.000+01:00",
                                                "time_seconds":1744,
                                                "status_code":"0",
                                                "time_neutralization":0,
                                                "time_adjusted":0,
                                                "time_penalty":0,
                                                "time_bonus":0,
                                                "points_final":0,
                                                "points_adjusted":0,
                                                "points_penalty":0,
                                                "points_bonus":0,
                                                "leg_number":1,
                                                "splits":[

                                                ],
                                                "result_type":{
                                                    "id":"e4ddfa9d-3347-47e4-9d32-c6c119aeac0e",
                                                    "description":"Stage"
                                                }
                                            }
                                        ],
                                        "club":{
                                            "id":"",
                                            "uuid":"",
                                            "city":"",
                                            "oe_key":"12",
                                            "short_name":"Galicia",
                                            "long_name":"Galicia"
                                        }
                                    },
                                    {
                                        "id":"",
                                        "uuid":"",
                                        "sicard":"8664444",
                                        "sex":"F",
                                        "first_name":"Ines",
                                        "last_name":"Pardo",
                                        "db_id":"11323",
                                        "iof_id":"",
                                        "bib_number":"1001-2",
                                        "sicard_alt":"",
                                        "runner_results":[
                                            {
                                                "id":"",
                                                "start_time":"2001-01-01T11:04:04.000+01:00",
                                                "finish_time":"2001-01-01T11:42:42.000+01:00",
                                                "time_seconds":2318,
                                                "status_code":"0",
                                                "time_neutralization":0,
                                                "time_adjusted":0,
                                                "time_penalty":0,
                                                "time_bonus":0,
                                                "points_final":0,
                                                "points_adjusted":0,
                                                "points_penalty":0,
                                                "points_bonus":0,
                                                "leg_number":2,
                                                "splits":[

                                                ],
                                                "result_type":{
                                                    "id":"e4ddfa9d-3347-47e4-9d32-c6c119aeac0e",
                                                    "description":"Stage"
                                                }
                                            }
                                        ],
                                        "club":{
                                            "id":"",
                                            "uuid":"",
                                            "city":"",
                                            "oe_key":"12",
                                            "short_name":"Galicia",
                                            "long_name":"Galicia"
                                        }
                                    }
                                ]
                            }
                        ]
                    }
                ]
            }
        ]
    }
}
```
It is important to notice the `event.id` MUST be the event ID on the url (in our example `79bad6e6-7c42-4317-958d-5c83c905b0ad`) and `event.stages.id` MUST be the **stage ID** selected in the stage selector (for example `995cdc24-66f9-4a64-bba6-90d6584475ac` )

## Useful links

- Check Oreplay server version https://www.oreplay.es/api/v1/ping/pong/
- Full API specification from Oreplay https://www.oreplay.es/api/v1/openapi/
- Organizers manual https://www.oreplay.es/organizers
- Open source repositories https://github.com/oreplay
