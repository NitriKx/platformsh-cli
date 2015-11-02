<?php
namespace Platformsh\Cli\Command\Environment;

use Platformsh\Cli\Command\PlatformCommand;
use Platformsh\Cli\Util\PropertyFormatter;
use Platformsh\Cli\Util\RelationshipsUtil;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EnvironmentRelationshipsCommand extends PlatformCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('environment:relationships')
          ->setAliases(array('relationships'))
          ->setDescription('Show an environment\'s relationships')
          ->addArgument('environment', InputArgument::OPTIONAL, 'The environment')
          ->addOption('property', null, InputOption::VALUE_REQUIRED, 'The relationship property to view');
        $this->addProjectOption()
             ->addEnvironmentOption()
             ->addAppOption();
        $this->addExample("View all the current environment's relationships");
        $this->addExample("View the 'master' environment's relationships", 'master');
        $this->addExample("View the 'master' environment's database port", 'master --property database.0.port');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->validateInput($input);

        $sshUrl = $this->getSelectedEnvironment()
          ->getSshUrl($input->getOption('app'));

        $util = new RelationshipsUtil($this->stdErr);
        $relationships = $util->getRelationships($sshUrl);
        if (!$relationships) {
            $this->stdErr->writeln('No relationships found');
            return 1;
        }

        $value = $relationships;
        $key = null;

        if ($property = $input->getOption('property')) {
            $parents = explode('.', $property);
            $key = end($parents);
            $value = self::getNestedArrayValue($relationships, $parents, $key_exists);
            if (!$key_exists) {
                $this->stdErr->writeln("Relationship property found: <error>$property</error>");

                return 1;
            }
        }

        $formatter = new PropertyFormatter();
        $formatter->jsonOptions = JSON_PRETTY_PRINT;
        $output->writeln($formatter->format($value, $key));

        return 0;
    }

    /**
     * Get a nested value in an array.
     *
     * @see Copied from \Drupal\Component\Utility\NestedArray::getValue()
     *
     * @param array $array
     * @param array $parents
     * @param bool  $key_exists
     *
     * @return mixed
     */
    protected static function &getNestedArrayValue(array &$array, array $parents, &$key_exists = NULL)
    {
        $ref = &$array;
        foreach ($parents as $parent) {
            if (is_array($ref) && array_key_exists($parent, $ref)) {
                $ref = &$ref[$parent];
            }
            else {
                $key_exists = FALSE;
                $null = NULL;
                return $null;
            }
        }
        $key_exists = TRUE;

        return $ref;
    }
}
