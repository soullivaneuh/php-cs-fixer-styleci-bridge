<?php

namespace SLLH\StyleCIBridge;

use SLLH\StyleCIBridge\Exception\LevelConfigException;
use SLLH\StyleCIBridge\StyleCI\Configuration;
use SLLH\StyleCIBridge\StyleCI\Fixers;
use SLLH\StyleCIBridge\StyleCI\Presets;
use StyleCI\Config\Config as StyleCIConfig;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\CS\Config\Config;
use Symfony\CS\Finder\DefaultFinder;
use Symfony\CS\Fixer;
use Symfony\CS\Fixer\Contrib\HeaderCommentFixer;
use Symfony\CS\FixerFactory;
use Symfony\CS\FixerInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class ConfigBridge
{
    const CS_FIXER_MIN_VERSION = '1.6.1';

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var FixerFactory
     */
    private $fixerFactory = null;

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
        if (version_compare(Fixer::VERSION, self::CS_FIXER_MIN_VERSION, '<')) {
            throw new \RuntimeException(sprintf(
                'PHP-CS-Fixer v%s is not supported, please upgrade to v%s or higher.',
                Fixer::VERSION,
                self::CS_FIXER_MIN_VERSION
            ));
        }

        $this->styleCIConfigDir = null !== $styleCIConfigDir ? $styleCIConfigDir : getcwd();
        $this->finderDirs = null !== $finderDirs ? $finderDirs : getcwd();
        $this->output = new ConsoleOutput();
        $this->output->getFormatter()->setStyle('warning', new OutputFormatterStyle('black', 'yellow'));
        // PHP-CS-Fixer 1.x BC
        if (class_exists('Symfony\CS\FixerFactory')) {
            $this->fixerFactory = FixerFactory::create();
            $this->fixerFactory->registerBuiltInFixers();
        }

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

        if (method_exists($config, 'setRules')) {
            $config->setRules($bridge->getRules());
        } else { // PHP-CS-Fixer 1.x BC
            $config->fixers($bridge->getFixers());
        }

        return $config
            ->finder($bridge->getFinder())
        ;
    }

    /**
     * @return Finder
     */
    public function getFinder()
    {
        $finder = DefaultFinder::create()->in($this->finderDirs);
        if (isset($this->styleCIConfig['finder'])) {
            $finderConfig = $this->styleCIConfig['finder'];
            foreach ($finderConfig as $key => $values) {
                $finderMethod = Container::camelize($key);
                foreach ($values as $value) {
                    if (method_exists($finder, $finderMethod)) {
                        $finder->$finderMethod($value);
                    } else {
                        $this->output->writeln(sprintf(
                            '<warning>Can not apply "%s" finder option with PHP-CS-Fixer v%s. You fixer config may be erroneous. Consider upgrading to fix it.</warning>',
                            str_replace('_', '-', $key),
                            Fixer::VERSION
                        ));
                    }
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
        $presetFixers = $this->resolveAliases($this->getPresetFixers());
        $enabledFixers = $this->resolveAliases($this->styleCIConfig['enabled']);
        $disabledFixers = $this->resolveAliases($this->styleCIConfig['disabled']);

        $fixers = array_merge(
            $enabledFixers,
            array_map(function ($disabledFixer) {
                return '-'.$disabledFixer;
            }, $disabledFixers),
            array_diff($presetFixers, $disabledFixers) // Remove disabled fixers from preset
        );

        // PHP-CS-Fixer 1.x BC
        if (method_exists('Symfony\CS\Fixer\Contrib\HeaderCommentFixer', 'getHeader') && HeaderCommentFixer::getHeader()) {
            array_push($fixers, 'header_comment');
        }

        return $fixers;
    }

    /**
     * Returns fixers converted to rules for PHP-CS-Fixer 2.x.
     *
     * @return array
     */
    public function getRules()
    {
        $fixers = $this->getFixers();

        $rules = array();
        foreach ($fixers as $fixer) {
            if ('-' === $fixer[0]) {
                $name = substr($fixer, 1);
                $enabled = false;
            } else {
                $name = $fixer;
                $enabled = true;
            }

            if ($this->isFixerAvailable($name)) {
                $rules[$name] = $enabled;
            } else {
                $this->output->writeln(sprintf('<warning>Fixer "%s" does not exist, skipping.</warning>', $name));
            }
        }

        return $rules;
    }

    /**
     * @return string[]
     */
    private function getPresetFixers()
    {
        $preset = $this->styleCIConfig['preset'];
        $validPresets = Presets::all();

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
        foreach (StyleCIConfig::ALIASES as $alias => $name) {
            if (in_array($alias, $fixers, true) && !in_array($name, $fixers, true) && $this->isFixerAvailable($name)) {
                array_push($fixers, $name);
            }
            if (in_array($name, $fixers, true) && !in_array($alias, $fixers, true) && $this->isFixerAvailable($alias)) {
                array_push($fixers, $alias);
            }
        }

        return $fixers;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function isFixerAvailable($name)
    {
        // PHP-CS-Fixer 1.x BC
        if (null === $this->fixerFactory) {
            return true;
        }

        return $this->fixerFactory->hasRule($name);
    }

    private function parseStyleCIConfig()
    {
        if (null === $this->styleCIConfig) {
            $config = Yaml::parse(file_get_contents(sprintf('%s/.styleci.yml', $this->styleCIConfigDir)));
            $processor = new Processor();
            $this->styleCIConfig = $processor->processConfiguration(new Configuration(), array('styleci' => $config));
        }
    }
}
