<?php

namespace Ibrows\BoxalinoBundle\Command;

use Ibrows\BoxalinoBundle\Exporter\Exporter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportPropertiesCommand
 * @package Ibrows\BoxalinoBundle\Command
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class ExportPropertiesCommand extends ContainerAwareCommand
{

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

        $this->publishProperties($output, $input->getOption('publish'));

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
     * @param bool|false $publish
     * @return bool
     */
    protected function publishProperties(OutputInterface $output, $publish = false)
    {
        if($publish){
            $response = $this->exporter->publishXmlChanges();
        }else{
            $response = $this->exporter->checkXmlChanges();
        }

        if (array_key_exists('error_type_number', $response)) {
            $output->writeln(sprintf('<error>Exporter exited with the following message: "%s"</error>', $response['message']));
            return false;
        }
        $changesInfo = $this->formatChanges($response['changes']);
        $info = <<<EOT
<info>The following changes have been made:</info>
<comment>$changesInfo</comment>

EOT;
        $output->writeln($info);
        return true;
    }

    /**
     * @param $changes
     * @return string
     */
    protected function formatChanges($changes)
    {
        if(!count($changes)){
            return 'no changes';
        }
        $str = '';


        foreach($changes['dbmind_inputs'] as $field){
            $str .= <<<EOT

Field:      $field[0]
Type:       $field[1]
Original:   $field[2]
New:        $field[3]

EOT;
        }

        return $str;
    }

}