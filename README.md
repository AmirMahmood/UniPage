# UniPage

## Usage
### Installation
* change your database Collation to `utf8mb4_general_ci`
* in `config` folder, rename `settings.php.sample` to `settings.php` and modify settings.
* set `timezone` in `settings.php`. use [this timezones list][2].
* Use `composer.json` to install dependencies with Composer (`composer update`)
* Set `public` folder as your web server root directory.
* open `/admin/install` url for installing.
* after installation, a user with username `root` and password `root` will be created. login with this user to `/admin/login` and then **delete this user or change its' password**.

### Upgrade
* first pull new updates from git repo
* Use `composer.json` to update dependencies with Composer (`composer update`)
* in admin page use `Upgrade` button to update database.
* in admin page clear cache with `Clear Cache` button to fix cache related problems.

### Maintenance
* make backup from your Database and `public/media` folder
* **Clear cache by `Clear Cache` button from admin page. by default cache is enable and sometimes it can cause some problems. clear cache if you are faced with with strange problems**

## Contribution
This app is written with `slimframework` as web framework, `twig` as template engine and `doctrine` as ORM.

Project structure is based on [this tutorial][1].

### Notices
* It seems Doctrine has a problem with boolean data type. when using `true` or `false` it can't flush data correctly.
use `0` or `1` instead.
* Doctrine cache can cause some unwanted behaviors. turn it of while development.

Useful resources:
* https://www.slimframework.com/docs/v4/
* https://twig.symfony.com/
* https://www.doctrine-project.org/
* https://github.com/slimphp/Slim-Skeleton

[1]: https://odan.github.io/2019/11/05/slim4-tutorial.html
[2]: https://www.php.net/manual/en/timezones.php
