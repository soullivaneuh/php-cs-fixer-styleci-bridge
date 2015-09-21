<?php

namespace SLLH\StyleCIBridge;

use SLLH\StyleCIBridge\Exception\LevelConfigException;
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

        return Config::create()
            ->finder($bridge->getFinder())
            ->level($bridge->getLevel())
            ->fixers($bridge->getFixers())
        ;
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
     */
    public function getLevel()
    {
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
        $fixers =  array_merge(
            isset($this->styleCIConfig['enabled']) ? $this->styleCIConfig['enabled'] : array(),
            isset($this->styleCIConfig['disabled']) ? array_map(function ($disabledFixer) {
                return '-'.$disabledFixer;
            }, $this->styleCIConfig['disabled']) : array()
        );

        if (HeaderCommentFixer::getHeader()) {
            array_push($fixers, 'header_comment');
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
