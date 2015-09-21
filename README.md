# PHP-CS-Fixer StyleCI bridge

Auto configure [PHP-CS-Fixer](http://cs.sensiolabs.org/) from [StyleCI](https://styleci.io/) config file.

This library permits to generate php-cs-fixer configuration directly from your `.styleci.yml` config file.

With that, you will avoid the pain of both config files maintenance.

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
