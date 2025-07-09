# Working with plugins
Plugins allow to code functionalities with a logical separation between them.

Follow cakephp [plugin](https://book.cakephp.org/4/en/plugins.html) documentation:
- Bake a plugin with `bin/cake bake plugin Thename`
- Add new directories to `composer.json` and refresh autoload cache with `composer dumpautoload`
  (remind the team to run this in every local laptop, or they may get errors)
- Remember to add the new route to tests in `phpunit.xml`
- Add new plugin to `migrationList()` in `app_rest/config/bootstrap.php`
- Add new plugin to `bootstrap()` in `app_rest/src/Application.php`
