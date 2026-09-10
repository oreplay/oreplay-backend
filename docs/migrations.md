# Migrations

We are working on two different databases:
- `phputesting` for unit testing (it will be created automatically when running tests)
- `app_rest` for local development (it will be created automatically when browsing [api/v1/ping/pong](http://localhost/api/v1/ping/pong))

Visiting that endpoint runs every pending migration, in every environment, which is how a deploy applies them. **Seeds do not
run unless `?seeds=true` is passed**: `api/v1/ping/pong?seeds=true`. Use `?migrations=false` to read the status without
applying anything.

Migrations should be the only way to perform changes in the database schema.

Plain sql scripts should be avoided due to:
- SQL injection
- Lack of database motor abstraction

Seeds: Will define the initial state of development the database
(and could be also defining the initial basic state for the production database)

Every seed only writes into a table that is **empty**, so running them against a populated database changes nothing — that is
what makes them safe to use for bootstrapping a fresh production database, first user included. They still have to be asked
for explicitly with `?seeds=true`, so that visiting a URL never writes data on its own.

More info about [phinx](https://book.cakephp.org/phinx/0/en/migrations.html) and the migration plugin on [cake book](https://book.cakephp.org/migrations/3/en/index.html)

```bash
#Go to the right folder
cd /var/www/cplatform/public/app_rest/


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


#Run composer install to get any new possible dependency
composer install
```
