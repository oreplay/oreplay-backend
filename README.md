# CakePHP Application Skeleton

A skeleton for creating applications with [CakePHP](https://cakephp.org) 4.x.

# License

All code is licensed under MIT License

# Serve and working with docker

We are using docker to develop run tests (where the code in local will be used in the server in real time), and make the system run in production.
Each docker service works as if it were an independent machines running on the same local network.

All details from those machines are defined in the `docker-compose` file. Here a few important notes:
- **Ports** definition: host machine : container
- **Links**: indicates dependencies between services
- **Volumes**: files are linked, so they are updated in the host and the container in real time.

Run from [docker-compose](https://docs.docker.com/compose/install/):

```
docker-compose -f ./docker-compose-dev.yml up -d
```

Running docker compose will start the http server (nginx) on port 80 and 443 by default (customizable from `docker-compose-dev.yml` file).
In order to use the ports 80 or 443 docker must have **Admin** rights.

Connect as `root` to the database launched using docker-compose (e.g. you can run exec on the container from nginx) and create a new `default` db for the project
(check `config/app_local.php` file for the credentials)

Connect to the nginx container using exec and **run composer**

```
cd /var/www/cplatform/public/app_rest/
composer install
```

After running composer install, the application should be served in http://localhost as root json page.

In order to get the database populated, the ping-check endpoint (a link is available in the root json page) should be called twice
(the first time will create the database and the second time will populate the database)

From the root json page, the events list endpoint is also referenced.
In similar way navigation within the endpoints should be possible using the `_links` property provided in each object
(using Firefox clicking on the links should work by default, in Chrome a [json-viewer](https://chromewebstore.google.com/detail/json-viewer/gbmdgpbipfallnflgajpaliibnhdgobh) extension may be needed)

## Configuring tests in Jetbrains to work with Docker

The local path is the location of this readme file, and it should be mapped to `/var/www/cplatform/public`

When running test use `/var/www/cplatform/public/app_rest/phpunit.xml.dist` as default configuration file.
Also add `/var/www/cplatform/public/app_rest/vendor/autoload.php` as a default autoload file

# Running commands inside docker

* Connect to the container using [exec](https://docs.docker.com/engine/reference/commandline/exec/)
* Navigate to the main path with `cd /var/www/cplatform/public/app_rest`
* Avoid running commands as root (since it can cause permission problems), change the user with: `su composeruser`

# REST API

Our REST API will follow these principles:
- Statelessness: Each HTTP request contains all information needed to complete the request.
- Well-defined HTTP methods: we are using GET, POST, PATCH and DELETE (as defined in the Controllers section) to perform different CRUD operations
- Uniform interface:
  - Universal syntax: Each resource should be addressed with one single URI
  - The API is sending and receiving data using JSON
  - HTTP response status codes. 20X codes will always be successful responses while 40X or 50X codes will be used for different errors (see list below).
- Self-explanatory: The API will use a common naming convention for URIs and include [HATEOAS](https://en.wikipedia.org/wiki/HATEOAS) to improve self discovery

## List of used [HTTP status codes](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)

Errors and success responses will be handled with standard HTTP status codes.

Error codes are handled in code throwing PHP exceptions. There are many exceptions for all common errors described below.
Some examples are `BadRequestException` (400), `NotFoundException` (404), `ForbiddenException` (403),
`InternalErrorException` (500), `NotImplementedException` (501), and some other exceptions with custom
behaviors like `SilentException` (do not add exception to logs), `ValidationException`
(to handle default model data validation with a 400 code), and `DetailedException` (the message will be displayed even in production)

| HTTP status | URL                                                       |
|-------------|-----------------------------------------------------------|
| 200         | OK                                                        |
| 201         | Created (on POST)                                         |
| 204         | No content (on DELETE)                                    |
| 301         | Moved permanently (redirect)                              |
| 302         | Found (redirect)                                          |
| 400         | Bad Request / Data validation error (ValidationException) |
| 401         | Unauthorized (login required)                             |
| 403         | Forbidden (user without access to action)                 |
| 404         | Not found (in lists use 200 empty array)                  |
| 405         | Method not allowed (invalid HTTP method)                  |
| 409         | Conflict (in checkout)                                    |
| 50x         | Server error                                              |

# Creating a new endpoint
Usually a new endpoint will require a new set of related objects.
- **Controller**: This is where the main logic of the endpoint is located.
- **Entity**: This is one single object usually returned in the endpoint. Normally one entity is an object representing a database row
- **Table**: This is where the definition of database relations and queries are defined
- **Test**: All code should be tested. All different classes should have test cases, this includes controllers (which should have ControllerTests)
- **Fixture**: The data used to work with **unit tests** should be defined in fixtures.

## Controllers
This repository uses [CakePHP](https://book.cakephp.org/4/en/index.html) [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) framework with a customized layer on top of it to work easier with Rest APIs.

Controllers will have different methods that can be overwritten to add functionality for each HTTP method.

Each different API URL must be linked to a controller.

The type data returned for each HTTP method will follow the standard described below.

A naming convention for routes and parameters in the routes are used (for example, the parameter userID will be checked against the authentication token)

Each controller must have a route defined in the `BasePlugin` file located in `src/` directory of each plugin
or in the `routes.php` file (when not using plugins)

Controllers could have defined the following methods (all inherited from \RestApi\Controller\RestApiController)
- isPublicController(): optional function to define if the controller is publicly available (without authentication)
- getMandatoryParams(): mandatory function where the mandatory params should be defined, so they are checked automatically (error will be thrown if the param is not defined)
- getList(): method called when an HTTP GET request without id is delivered by the server. Used to retrieve many entities.
- getData($id): method called when an HTTP GET request WITH id is delivered by the server. Used to retrieve one entity.
- addNew($data): method called when an HTTP POST request is delivered by the server. Used to create a new entity.
- edit($id, $data): method called when an HTTP PATCH request is delivered by the server. Used to edit one entity.
- delete($id): method called when an HTTP DELETE request is delivered by the server. Used to delete one entity.

Description of HTTP methods and functions in controllers:

| HTTP method | URL          | Controller Method | Description              | Returns          | HTTP success status |
|-------------|--------------|-------------------|--------------------------|------------------|---------------------|
| POST        | /entity      | addNew($data)     | Create a new entity      | Entity created   | 201                 |
| GET         | /entity      | getList()         | Get list                 | List of entities | 200                 |
| GET         | /entity/{id} | getData($id)      | Get one entity           | Single entity    | 200                 |
| PATCH       | /entity/{id} | edit($id, $data)  | Edit                     | Entity modified  | 200                 |
| DELETE      | /entity/{id} | delete($id)       | Deletes single entity    | (no content)     | 204                 |
| OPTIONS     | *            | (automatic)       | (check ApiCorsComponent) |                  | 200                 |
| PUT         | /entity/{id} | put($id, $data)   | ~~Edit~~ (not in use)    | Entity modified  | 200                 |


## Model
The model level used is the one defined by cakephp with 2 different classes (Tables and Entities).

Queries are built from tables (or if they are very simple, directly from controllers). The different queries are defined in [cakephp documentation](https://book.cakephp.org/5/en/orm/query-builder.html)

# Testing

You can run [tests](https://book.cakephp.org/4/en/development/testing.html) using phpunit command: `vendor/bin/phpunit -c ../app_rest/phpunit.xml.dist`

But using an **IDE** is desirable (e.g. [PhpStorm](https://www.jetbrains.com/phpstorm/))

Generate test coverage with: `vendor/bin/phpunit --coverage-html ./webroot/coverage/*`

# Working with plugins
Plugins allow to code functionalities with a logical separation between them.

Follow cakephp [plugin](https://book.cakephp.org/4/en/plugins.html) documentation:
- Bake a plugin with `bin/cake bake plugin Thename`
- Add new directories to `composer.json` and refresh autoload cache with `composer dumpautoload`
  (remind the team to run this in every local laptop, or they may get errors)
- Remember to add the new route to tests in `phpunit.xml`
- Add new plugin to `migrationList()` in `app_rest/config/bootstrap.php`
- Add new plugin to `bootstrap()` in `app_rest/src/Application.php`

# Migrations

We are working on two different databases:
- `phputesting` for unit testing (it will be created automatically when running tests)
- `app_rest` for local development (it will be created automatically when browsing [api/v1/ping/pong](http://localhost/api/v1/ping/pong))

Migrations should be the only way to perform changes in the database schema.

Plain sql scripts should be avoided due to:
- SQL injection
- Lack of database motor abstraction

Seeds: Will define the initial state of development the database
(and could be also defining the initial basic state for the production database)

More info about [phinx](https://book.cakephp.org/phinx/0/en/migrations.html) and the migration plugin on [cake book](https://book.cakephp.org/migrations/3/en/index.html)

```
# create a new migration called 'CreateUsers'
bin/cake bake migration CreateUsers
# create a new migration called 'MyNewMigration' for the 'PluginName' (using -p for plugins)
bin/cake bake migration MyNewMigration -p PluginName

# execute the migration on the db
bin/cake migrations migrate

# revert the migration on the db
bin/cake migrations rollback

# create new seeds
bin/cake bake seed Users

# run seeder
bin/cake migrations seed

# run specific seeder class
bin/cake migrations seed --seed UsersSeed
```
# Devops

We will use sonarqube community edition (free). For that is needed to be installed as a docker container, with the following command:

```
docker run -d --name sonarqube -e SONAR_ES_BOOTSTRAP_CHECKS_DISABLE=true -p 9000:9000 sonarqube:latest
```
