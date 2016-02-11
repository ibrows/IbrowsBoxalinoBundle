<?php
/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Command;

use Ibrows\BoxalinoBundle\Exporter\Exporter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportPropertiesCommand extends ContainerAwareCommand
{
    const SYNC_FULL = 'full';
    const SYNC_DELTA = 'delta';
    const SYNC_PARTIAL = 'partial';
    const SYNC_PROPERTIES = 'properties';

    protected $syncStrategies = array('full', 'delta', 'partial', 'properties');

    /**
     * @var Exporter
     */
    protected $exporter;


    /**
     * configuration for the command
     */
    protected function configure()
    {
        $this->setName('ibrows:boxalino:export-properties')
            ->setDescription('Export the properties xml to boxalino and publish changes')
            ->addOption('properties-xml', null, InputOption::VALUE_OPTIONAL, 'Path to properties xml file will override configured file')
            ->addOption('publish', null, InputOption::VALUE_NONE, 'If the Export should be published')
            ->setHelp(<<<EOT
The <info>%command.name%</info> exports the properties xml :

    <info>php %command.full_name%</info>

The <info>--publish</info> parameter has to be used to actually publish the changes to boxalino.
EOT
            );

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->exporter = $this->getContainer()->get('ibrows_boxalino.exporter.exporter');

        $result = $this->exportProperties($output, $input->getOption('properties-xml'));

        if (!$result) {
            return 1;
        }

        if ($input->getOption('publish')) {
            $this->publishProperties($output);
        }

        return 0;
    }


    /**
     * @param OutputInterface $output
     * @param null $propertiesXml
     * @return bool
     */
    protected function exportProperties(OutputInterface $output, $propertiesXml = null)
    {
        if ($propertiesXml) {
            $this->exporter->setPropertiesXml($propertiesXml);
        }

        $response = $this->exporter->pushXml();

        if (array_key_exists('error_type_number', $response)) {
            $output->writeln(sprintf('<error>Exporter exited with the following message: "%s"</error>', $response['message']));
            return false;
        }
        $output->writeln('<info>Properties XML successfully exported</info>');
        return true;
    }

    /**
     * @param OutputInterface $output
     * @return bool
     */
    protected function publishProperties(OutputInterface $output)
    {
        $response = $this->exporter->publishXml();

        if (array_key_exists('error_type_number', $response)) {
            $output->writeln(sprintf('<error>Exporter exited with the following message: "%s"</error>', $response['message']));
            return false;
        }
        $output->writeln('<info>Properties XML successfully published</info>');
        return true;
    }

}