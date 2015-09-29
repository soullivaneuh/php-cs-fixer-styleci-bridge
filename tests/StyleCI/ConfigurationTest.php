<?php

namespace SLLH\StyleCIBridge\Tests\StyleCI;

use Matthias\SymfonyConfigTest\PhpUnit\AbstractConfigurationTestCase;
use SLLH\StyleCIBridge\StyleCI\Configuration;

class ConfigurationTest extends AbstractConfigurationTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getConfiguration()
    {
        return new Configuration();
    }

    /**
     * @dataProvider validConfigurations
     *
     * @param array $configuration
     */
    public function testValidConfiguration(array $configuration)
    {
        $this->assertConfigurationIsValid(array('styleci' => $configuration));
    }

    public function validConfigurations()
    {
        return array(
            array(array(
                'preset' => 'psr1',
            )),
            array(array(
                'preset' => 'psr2',
            )),
            array(array(
                'preset' => 'symfony',
            )),
            array(array(
                'preset' => 'laravel',
            )),
            array(array(
                'preset' => 'recommended',
            )),
            array(array(
                'preset'  => 'symfony',
                'linting' => false,
                'enabled' => array(
                    'return',
                    'phpdoc_params',
                ),
                'disabled' => array(
                    'short_array_syntax',
                ),
                'finder' => array(
                    'not-name' => array('*.dummy'),
                ),
            )),
            array(array(
                'preset'  => 'symfony',
                'enabled' => array(
                    'align_double_arrow',
                ),
                'disabled' => array(
                    'unalign_double_arrow',
                ),
            )),
        );
    }

    /**
     * @dataProvider invalidConfigurations
     *
     * @param array $configuration
     */
    public function testInvalidConfiguration(array $configuration)
    {
        $this->assertConfigurationIsInvalid(array('styleci' => $configuration));
    }

    public function invalidConfigurations()
    {
        return array(
            array(array(
            )),
            array(array(
                'preset' => 'dummy',
            )),
            array(array(
                'preset'  => 'symfony',
                'linting' => 42,
            )),
            array(array(
                'preset'  => 'symfony',
                'linting' => false,
                'enabled' => false,
            )),
            array(array(
                'preset'   => 'symfony',
                'disabled' => false,
            )),
            array(array(
                'preset'  => 'symfony',
                'enabled' => array(
                    'dummy',
                    'phpdoc_params',
                ),
            )),
            array(array(
                'preset'   => 'symfony',
                'disabled' => array(
                    'dummy',
                    'short_array_syntax',
                ),
            )),
            array(array(
                'preset'  => 'symfony',
                'finder'  => array(
                    'not-existing-method' => array('*.dummy'),
                ),
            )),
            array(array(
                'preset'  => 'symfony',
                'enabled' => array(
                    'align_double_arrow',
                ),
            )),
            array(array(
                'preset'  => 'psr1',
                'enabled' => array(
                    'no_blank_lines_before_namespace',
                    'single_blank_line_before_namespace',
                ),
            )),
        );
    }
}
