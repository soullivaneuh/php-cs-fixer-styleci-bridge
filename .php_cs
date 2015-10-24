<?php

require __DIR__.'/autoload.php';

use SLLH\StyleCIBridge\ConfigBridge;

$config = ConfigBridge::create();
$config->setUsingCache(true);
$config->setRiskyAllowed(true);

return $config;
