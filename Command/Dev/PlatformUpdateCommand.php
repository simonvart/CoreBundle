<?php

namespace Claroline\CoreBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Claroline\InstallationBundle\Bundle\BundleVersion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Installs the platform, optionaly with plugins and data fixtures.
 */
class PlatformUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('claroline:update')
            ->setDescription('Update the platform.');
        $this->setDefinition(
            array(
                new InputArgument('to_version', InputArgument::REQUIRED, 'to version'),
                new InputArgument('to_migration', InputArgument::REQUIRED, 'to migration')
            )
        );
    }
    
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $params = array(
            'to_version' => 'to version: ',
            'to_migration' => 'to migration: '
        );

        foreach ($params as $argument => $argumentName) {
            if (!$input->getArgument($argument)) {
                $input->setArgument(
                    $argument, $this->askArgument($output, $argumentName)
                );
            }
        }
    }
    
    protected function askArgument(OutputInterface $output, $argumentName)
    {
        $argument = $this->getHelper('dialog')->askAndValidate(
            $output,
            $argumentName,
            function ($argument) {
                if (empty($argument)) {
                    throw new \Exception('This argument is required');
                }

                return $argument;
            }
        );

        return $argument;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $installer = $this->getContainer()->get('claroline.installation.manager');
        $installer->setLogger(function ($message) use ($output) {
           $output->writeln($message);
        });
        $bundle = $this->getContainer()->get('kernel')->getBundle('ClarolineCoreBundle');
        
        $createDbCommand = $this->getApplication()->find('doctrine:database:create');
        $createDbInput = new ArrayInput(
            array(
                'command' => 'doctrine:database:create'
            )
        );
        $createDbCommand->run($createDbInput, $output);

        $vto = $input->getArgument('to_version');
        $mto = $input->getArgument('to_migration');
        
        $from = new BundleVersion('0.0', '0', '0');
        $to = new BundleVersion($vto . '.0', $vto, $mto);
        $installer->update($bundle, $from, $to);
    }
}
