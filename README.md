# CakePHP Application Skeleton

A skeleton for creating applications with [CakePHP](https://cakephp.org) 4.x.

# License

All code is licensed under MIT License

# Serve
Run from [docker-compose](https://docs.docker.com/compose/install/):

```
docker-compose -f ./docker-compose-dev.yml up -d
```

Connect as `root` to the database launched using docker-compose (e.g. you can run exec on the container from nginx) and create a new `default` db for the project
(check `config/app_local.php` file for the credentials)

Connect to the nginx container using exec and run composer (do not run as root, but in order to set folder permissions root may be needed)

```
cd /var/www/cplatform/public/app_rest/
su composeruser
composer install
```

# Working with docker

The local path is the location of this readme file and it should be mapped to `/var/www/cplatform/public`

When running test use `/var/www/cplatform/public/app_rest/phpunit.xml.dist` as default configuration file.
Also add `/var/www/cplatform/public/app_rest/vendor/autoload.php` as a default autoload file

# Running commands inside docker

* Connect to the container using [exec](https://docs.docker.com/engine/reference/commandline/exec/)
* Navigate to the main path with `cd /var/www/cplatform/public/app_rest`
* Avoid running commands as root (since it can cause permission problems), change the user with: `su composeruser`

# Creating a new endpoint
Usually a new endpoint will require a new set of related objects.
- Controller: This is where the main logic of the endpoint is located
- Entity: This is one single object usually returned in the endpoint. Normally one entity is an object representing a database row
- Table: This is where the definition of database relations and queries are defined
- Test: All code should be tested. All different classes should have test cases, this includes controllers (which should have ControllerTests)
- Fixture: The data used to work with tests should be defined in fixtures.

## Controllers
Each controller must have a route defined in the BasePlugin file located in src/ directory of each plugin 
or in the routes.php file (when not using plugins)

Controllers could have defined the following methods (all inherited from \RestApi\Controller\RestApiController)
- isPublicController(): optional function to define if the controller is publicly available (without authentication)
- getMandatoryParams(): mandatory function where the mandatory params should be defined, so they are checked automatically (error will be thrown if the param is not defined)
- getList(): method called when an HTTP GET request without id is delivered by the server. Used to retrieve many entities.
- getData($id): method called when an HTTP GET request WITH id is delivered by the server. Used to retrieve one entity.
- addNew($data): method called when an HTTP POST request is delivered by the server. Used to create a new entity.
- edit($id, $data): method called when an HTTP PATCH request is delivered by the server. Used to edit one entity.
- delete($id): method called when an HTTP DELETE request is delivered by the server. Used to delete one entity.

## Model
The model level used is the one defined by cakephp with 2 different classes (Tables and Entities).

Queries are built from tables (or if they are very simple, directly from controllers). The different queries are defined in [cakephp documentation](https://book.cakephp.org/5/en/orm/query-builder.html)

# Testing

You can run [tests](https://book.cakephp.org/4/en/development/testing.html) using phpunit command: `vendor/bin/phpunit -c ../app_rest/phpunit.xml.dist`

But using an **IDE** is desirable (e.g. PhpStorm)

Generate test coverage with: `vendor/bin/phpunit --coverage-html ./webroot/coverage/*`

# Working with plugins
Plugins allow to code functionalities with a logical separation between them.

Follow cakephp [plugin](https://book.cakephp.org/4/en/plugins.html) documentation:
- Bake a plugin with `bin/cake bake plugin Thename`
- Add new directories to `composer.json` and refresh autoload cache with `composer dumpautoload` (remind the team to run this in every local laptop, or they may get errors)
- Remember to add the new route to tests in `phpunit.xml`
- Add new plugin to `migrationList()` in `app_rest/config/bootstrap.php`
- Add new plugin to `bootstrap()` in `app_rest/src/Application.php`

# Migrations

Migrations should be the only way to perform changes in the database schema

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
