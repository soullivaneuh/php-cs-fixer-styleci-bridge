<?php

namespace SLLH\StyleCIBridge\StyleCI;

use StyleCI\Config\Config as StyleCIConfig;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class Presets
{
    public static function all()
    {
        return array(
            'psr1'        => StyleCIConfig::PSR1_FIXERS,
            'psr2'        => StyleCIConfig::PSR2_FIXERS,
            'symfony'     => StyleCIConfig::SYMFONY_FIXERS,
            'laravel'     => StyleCIConfig::LARAVEL_FIXERS,
            'recommended' => StyleCIConfig::RECOMMENDED_FIXERS,
        );
    }
}
