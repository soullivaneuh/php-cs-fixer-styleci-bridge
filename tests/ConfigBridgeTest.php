<?php

namespace SLLH\StyleCIBridge\Tests;

use SLLH\StyleCIBridge\ConfigBridge;
use Symfony\CS\FixerInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class ConfigBridgeTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConfig()
    {
        $config = ConfigBridge::create(__DIR__.'/Fixtures/configs/default');

        $this->assertSame(array(
            'align_double_arrow',
            'newline_after_open_tag',
            'ordered_use',
            'long_array_syntax',
            '-psr0',
            '-unalign_double_arrow',
            '-unalign_equals',
        ), $config->getFixers());

        $this->assertSame(FixerInterface::SYMFONY_LEVEL, $config->getLevel());
    }
}
