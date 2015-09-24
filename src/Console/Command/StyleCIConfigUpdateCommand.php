<?php

namespace SLLH\StyleCIBridge\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\CS\Tokenizer\Token;
use Symfony\CS\Tokenizer\Tokens;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class StyleCIConfigUpdateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('styleci:config:update')
            ->setDescription('Update StyleCI fixers config from official repository.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            throw new \RuntimeException('You must run this command under PHP 5.6 or higher.');
        }

        $fixersTab = array();

        $configClass = file_get_contents('https://github.com/StyleCI/Config/raw/master/src/Config.php');

        /** @var Tokens|Token[] $tokens */
        $tokens = Tokens::fromCode($configClass);
        /*
         * @var int
         * @var Token
         */
        foreach ($tokens->findGivenKind(T_CONST) as $index => $token) {
            if ('[' === $tokens[$index + 6]->getContent()) {
                $name = strtolower($tokens[$index + 2]->getContent());
                $fixers = array();
                for ($i = $index + 7; ']' !== $tokens[$i]->getContent(); ++$i) {
                    if ($tokens[$i]->isGivenKind(T_CONSTANT_ENCAPSED_STRING) && ',' === $tokens[$i + 1]->getContent()) {
                        // Simple array management
                        array_push($fixers, array('name' => $this->getString($tokens[$i]->getContent())));
                    } elseif ($tokens[$i]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                        // Double arrow management
                        $key = $this->getString($tokens[$i]->getContent());
                        for (++$i; $tokens[$i]->isGivenKind(T_DOUBLE_ARROW); ++$i) {
                        }
                        $i += 3;
                        array_push($fixers, array(
                            'key'  => $key,
                            'name' => $this->getString($tokens[$i]->getContent()),
                        ));
                    }
                }
                $fixersTab[$name] = $fixers;
            }
        }

        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem(__DIR__.'/../..'));
        file_put_contents(__DIR__.'/../../StyleCI/Fixers.php', $twig->render('StyleCI/Fixers.php.twig', array('fixersTab' => $fixersTab)));
    }

    /**
     * @param string $tokenContent
     *
     * @return string
     */
    private function getString($tokenContent)
    {
        return str_replace(array('"', "'"), '', $tokenContent);
    }
}
