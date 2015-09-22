# PHP-CS-Fixer StyleCI bridge

Auto configure [PHP-CS-Fixer](http://cs.sensiolabs.org/) from [StyleCI](https://styleci.io/) config file.

This library permits to generate php-cs-fixer configuration directly from your `.styleci.yml` config file.

With that, you will avoid the pain of both config files maintenance.

[![Latest Stable Version](https://poser.pugx.org/sllh/php-cs-fixer-styleci-bridge/v/stable)](https://packagist.org/packages/sllh/php-cs-fixer-styleci-bridge)
[![Latest Unstable Version](https://poser.pugx.org/sllh/php-cs-fixer-styleci-bridge/v/unstable)](https://packagist.org/packages/sllh/php-cs-fixer-styleci-bridge)
[![License](https://poser.pugx.org/sllh/php-cs-fixer-styleci-bridge/license)](https://packagist.org/packages/sllh/php-cs-fixer-styleci-bridge)
[![Dependency Status](https://www.versioneye.com/php/sllh:php-cs-fixer-styleci-bridge/1.0.0/badge.svg)](https://www.versioneye.com/php/sllh:php-cs-fixer-styleci-bridge)
[![Reference Status](https://www.versioneye.com/php/sllh:php-cs-fixer-styleci-bridge/reference_badge.svg)](https://www.versioneye.com/php/sllh:php-cs-fixer-styleci-bridge/references)

[![Total Downloads](https://poser.pugx.org/sllh/php-cs-fixer-styleci-bridge/downloads)](https://packagist.org/packages/sllh/php-cs-fixer-styleci-bridge)
[![Monthly Downloads](https://poser.pugx.org/sllh/php-cs-fixer-styleci-bridge/d/monthly)](https://packagist.org/packages/sllh/php-cs-fixer-styleci-bridge)
[![Daily Downloads](https://poser.pugx.org/sllh/php-cs-fixer-styleci-bridge/d/daily)](https://packagist.org/packages/sllh/php-cs-fixer-styleci-bridge)

[![Build Status](https://travis-ci.org/Soullivaneuh/php-cs-fixer-styleci-bridge.svg?branch=master)](https://travis-ci.org/Soullivaneuh/php-cs-fixer-styleci-bridge)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Soullivaneuh/php-cs-fixer-styleci-bridge/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Soullivaneuh/php-cs-fixer-styleci-bridge/?branch=master)
[![Code Climate](https://codeclimate.com/github/Soullivaneuh/php-cs-fixer-styleci-bridge/badges/gpa.svg)](https://codeclimate.com/github/Soullivaneuh/php-cs-fixer-styleci-bridge)
[![Coverage Status](https://coveralls.io/repos/Soullivaneuh/php-cs-fixer-styleci-bridge/badge.svg?branch=master)](https://coveralls.io/r/Soullivaneuh/php-cs-fixer-styleci-bridge?branch=master)

## Who is using this?

You can see which projects are using this package on the dedicated [Packagist page](https://packagist.org/packages/sllh/php-cs-fixer-styleci-bridge/dependents).

## Installation

Include this library on your dev dependencies:

```bash
composer require --dev sllh/php-cs-fixer-styleci-bridge
```

## Usage

You can use this bridge with several manners.

### Basic usage

Put the following config on your `.php_cs` file:
 
```php
<?php

// Needed to get styleci-bridge loaded
require_once './vendor/autoload.php';

use SLLH\StyleCIBridge\ConfigBridge;

return ConfigBridge::create();
```

With this configuration, the configuration bridge will just parse your `.styleci.yml` file.

Sample working file:

```yaml
preset: symfony

enabled:
  - align_double_arrow
  - newline_after_open_tag
  - ordered_use
  - long_array_syntax

disabled:
  - psr0
  - unalign_double_arrow
  - unalign_equals
```

### Directories options

You can change default repository of your `.styleci.yml` file and directories for the CS Finder directly on `ConfigBridge::create` method or constructor.

```php
<?php

require_once './vendor/autoload.php';

use SLLH\StyleCIBridge\ConfigBridge;

return ConfigBridge::create(__DIR__.'/config', [__DIR__, __DIR__.'../lib']);
```

### Customize the configuration class

`ConfigBridge::create` returns a `Symfony\CS\Config\Config` that you can customize as you want.

```php
<?php

require_once './vendor/autoload.php';

use SLLH\StyleCIBridge\ConfigBridge;

return ConfigBridge::create()
    ->setUsingCache(true) // Enable the cache
;
```

### Using the bridge

You can also using bridge method, part by part.

```php
<?php

require_once './vendor/autoload.php';

use SLLH\StyleCIBridge\ConfigBridge;
use Symfony\CS\Config\Config;

$bridge = new ConfigBridge();

return Config::create()
    ->finder($bridge->getFinder())
    ->fixers(['dummy', 'foo', '-bar'])
    ->setUsingCache(true)
;
```

### Header comment

Unfortunately, header comment option [is not available](https://twitter.com/soullivaneuh/status/644795113399582720) on StyleCI config file.
 
You will have to copy it from StyleCI web interface and set it manually.

```php
<?php

use SLLH\StyleCIBridge\ConfigBridge;
use Symfony\CS\Fixer\Contrib\HeaderCommentFixer;

$header = <<<EOF
This file is part of the dummy package.

(c) John Doe <john@doe.com>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

HeaderCommentFixer::setHeader($header);

return ConfigBridge::create();
```

The config bridge will automatically detect the fixer and add it on CS configuration.
