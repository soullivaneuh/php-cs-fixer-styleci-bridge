<?php

namespace SLLH\StyleCIBridge;

use SLLH\StyleCIBridge\Exception\FixersConfigException;
use SLLH\StyleCIBridge\Exception\LevelConfigException;
use SLLH\StyleCIBridge\Exception\PresetConfigException;
use SLLH\StyleCIBridge\StyleCI\Fixers;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\CS\Config\Config;
use Symfony\CS\Finder\DefaultFinder;
use Symfony\CS\Fixer\Contrib\HeaderCommentFixer;
use Symfony\CS\FixerInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class ConfigBridge
{
    /**
     * @var string
     */
    private $styleCIConfigDir;

    /**
     * @var array|null
     */
    private $styleCIConfig = null;

    /**
     * @var string|array
     */
    private $finderDirs;

    /**
     * @param string|null       $styleCIConfigDir StyleCI config directory. Called script dir as default.
     * @param string|array|null $finderDirs       A directory path or an array of directories for Finder. Called script dir as default.
     */
    public function __construct($styleCIConfigDir = null, $finderDirs = null)
    {
        $this->styleCIConfigDir = null !== $styleCIConfigDir ? $styleCIConfigDir : getcwd();
        $this->finderDirs = null !== $finderDirs ? $finderDirs : getcwd();

        $this->parseStyleCIConfig();
    }

    /**
     * @param string       $styleCIConfigDir
     * @param string|array $finderDirs       A directory path or an array of directories for Finder
     *
     * @return Config
     */
    public static function create($styleCIConfigDir = null, $finderDirs = null)
    {
        $bridge = new static($styleCIConfigDir, $finderDirs);

        $config = Config::create();

        // PHP-CS-Fixer 1.x BC
        if (method_exists($config, 'level')) {
            $config->level(FixerInterface::NONE_LEVEL);
        }

        return $config
            ->finder($bridge->getFinder())
            ->fixers($bridge->getFixers());
    }

    /**
     * @return Finder
     */
    public function getFinder()
    {
        $finder = DefaultFinder::create()->in($this->finderDirs);
        if (isset($this->styleCIConfig['finder']) && is_array($this->styleCIConfig['finder'])) {
            $finderConfig = $this->styleCIConfig['finder'];
            foreach ($finderConfig as $key => $values) {
                $finderMethod = Container::camelize(str_replace('-', '_', $key));
                foreach ($values as $value) {
                    $finder->$finderMethod($value);
                }
            }
        }

        return $finder;
    }

    /**
     * @return int
     *
     * @deprecated since 1.1, to be removed in 2.0
     */
    public function getLevel()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.1 and will be removed in 2.0.', E_USER_DEPRECATED);

        if (!isset($this->styleCIConfig['preset'])) {
            throw new LevelConfigException('You must define a preset on StyleCI configuration file.');
        }

        $preset = $this->styleCIConfig['preset'];
        $validPresets = array(
            'psr1'    => FixerInterface::PSR1_LEVEL,
            'psr2'    => FixerInterface::PSR1_LEVEL,
            'symfony' => FixerInterface::SYMFONY_LEVEL,
        );
        if (!in_array($preset, array_keys($validPresets))) {
            throw new LevelConfigException(sprintf('Invalid preset "%s". Must be one of "%s".', $preset, implode('", "', array_keys($validPresets))));
        }

        return $validPresets[$preset];
    }

    /**
     * @return string[]
     */
    public function getFixers()
    {
        $presetFixers = $this->getPresetFixers();
        $enabledFixer = isset($this->styleCIConfig['enabled']) ? $this->styleCIConfig['enabled'] : array();
        $disabledFixer = isset($this->styleCIConfig['disabled']) ? $this->styleCIConfig['disabled'] : array();

        // Aliases should be included as valid fixers
        $invalidFixers = array_diff(array_merge($enabledFixer, $disabledFixer), array_merge(Fixers::$valid, array_keys(Fixers::$aliases)));
        if (count($invalidFixers) > 0) {
            throw new FixersConfigException(sprintf('The following fixers are invalid: "%s".', implode('", "', $invalidFixers)));
        }

        $presetFixers = $this->resolveAliases($presetFixers);
        $enabledFixer = $this->resolveAliases($enabledFixer);
        $disabledFixer = $this->resolveAliases($disabledFixer);

        $fixers = array_merge(
            $enabledFixer,
            array_map(function ($disabledFixer) {
                return '-'.$disabledFixer;
            }, $disabledFixer),
            array_diff($presetFixers, $disabledFixer) // Remove disabled fixers from preset
        );

        if (HeaderCommentFixer::getHeader()) {
            array_push($fixers, 'header_comment');
        }

        return $fixers;
    }

    /**
     * @return string[]
     */
    private function getPresetFixers()
    {
        if (!isset($this->styleCIConfig['preset'])) {
            throw new PresetConfigException('You must define a preset on StyleCI configuration file.');
        }

        $preset = $this->styleCIConfig['preset'];
        $validPresets = array(
            'psr1'        => Fixers::$psr1_fixers,
            'psr2'        => Fixers::$psr2_fixers,
            'symfony'     => Fixers::$symfony_fixers,
            'laravel'     => Fixers::$laravel_fixers,
            'recommended' => Fixers::$recommended_fixers,
        );
        if (!in_array($preset, array_keys($validPresets))) {
            throw new PresetConfigException(sprintf('Invalid preset "%s". Must be one of "%s".', $preset, implode('", "', array_keys($validPresets))));
        }

        return $validPresets[$preset];
    }

    /**
     * Adds both aliases and real fixers if set. PHP-CS-Fixer would not take care if not existing.
     * Better compatibility between PHP-CS-Fixer 1.x and 2.x.
     *
     * @param string[] $fixers
     *
     * @return string[]
     */
    private function resolveAliases(array $fixers)
    {
        foreach (Fixers::$aliases as $alias => $name) {
            if (in_array($alias, $fixers, true) && !in_array($name, $fixers, true)) {
                array_push($fixers, $name);
            }
            if (in_array($name, $fixers, true) && !in_array($alias, $fixers, true)) {
                array_push($fixers, $alias);
            }
        }

        return $fixers;
    }

    private function parseStyleCIConfig()
    {
        if (null === $this->styleCIConfig) {
            $this->styleCIConfig = Yaml::parse(file_get_contents(sprintf('%s/.styleci.yml', $this->styleCIConfigDir)));
        }
    }
}
