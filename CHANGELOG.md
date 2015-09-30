# CHANGELOG

* 1.3.1 (2015-09-30)

 * Use `FixerFactory::hasRule` instead of building our own fixers array by name.

* 1.3.0 (2015-09-29)

 * PHP-CS-Fixer version check.
 * Use Symfony Config component for `.styleci.yml` parsing.
 * Manage fixers conflicts.

* 1.2.1 (2015-09-28)

 * Fix some BC breaks with PHP-CS-Fixer 2.0.

* 1.2.0 (2015-09-25)

 * Resolve fixer aliases for better compatibility with PHP-CS-Fixer 1.x and 2.x.

* 1.1.0 (2015-09-24)

 * Load fixers from StyleCI config instead of PHP-CS-Fixer.
 * Deprecate `ConfigBridge::getFixers`.
