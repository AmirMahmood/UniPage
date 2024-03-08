# UniPage

## Deployment
* in `php.ini` set `date.timezone`. for example `date.timezone = "Asia/Tehran"`. use [this list][2] for more timezones.
* in `config` folder, rename `settings.php.sample` to `settings.php` and modify settings.
* Use `composer.json` to install dependencies with Composer (`composer update`)
* Set `public` folder as your web server root directory.

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
