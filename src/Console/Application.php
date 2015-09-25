<?php

namespace SLLH\StyleCIBridge\Console;

use SLLH\StyleCIBridge\Console\Command\StyleCIConfigCheckCommand;
use SLLH\StyleCIBridge\Console\Command\StyleCIConfigUpdateCommand;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class Application extends BaseApplication
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->add(new StyleCIConfigUpdateCommand());
        $this->add(new StyleCIConfigCheckCommand());
    }
}
