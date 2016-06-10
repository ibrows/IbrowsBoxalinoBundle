<?php
namespace Ibrows\BoxalinoBundle\Command;

use Ibrows\BoxalinoBundle\Exporter\Exporter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportEntitiesCommand
 * @package Ibrows\BoxalinoBundle\Command
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class ExportEntitiesCommand extends ContainerAwareCommand
{
    /**
     *
     */
    const SYNC_FULL = 'full';
    /**
     *
     */
    const SYNC_DELTA = 'delta';

    /**
     * @var array
     */
    protected $syncStrategies = array('full', 'delta');

    /**
     * @var Exporter
     */
    protected $exporter;

    /**
     * @var bool
     */
    protected $dryRun = false;
    /**
     * configuration for the command
     */
    protected function configure()
    {
        $this->setName('ibrows:boxalino:export-entities')
            ->setDescription('Export configured entities to boxalino')
            ->addOption('sync', null, InputOption::VALUE_REQUIRED, 'Sync strategy, possible values are full, delta', 'full')
            ->addOption('push-live', null, InputOption::VALUE_NONE, 'If the Export should be pushed to the Live index, otherwise it is always pushed to dev')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Just create CSV with no push')
            ->setHelp(<<<EOT
The <info>%command.name%</info> exports the configured entities in csv files to boxalino:

    <info>php %command.full_name%</info>

The <info>--sync</info> should be set to full, delta, partial or properties

The <info>--push-live</info> parameter has to be used to push to the live index, default is dev.
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

        $syncType = $input->getOption('sync');

        if (!in_array($syncType, $this->syncStrategies)) {
            $output->writeln(sprintf('<error>Sync stategy %s is not supported, possible options are full, delta and partial</error>', $syncType));
            return 1;
        }
        $this->exporter = $this->getContainer()->get('ibrows_boxalino.exporter.exporter');

        if ($input->getOption('push-live')) {
            $this->exporter->setDevIndex(false);
        }

        $this->dryRun = $input->getOption('dry-run');

        switch ($syncType) {
            case self::SYNC_FULL:
                $this->exportFull($output);
                break;
            case self::SYNC_DELTA:
                $this->exportDelta($output);
                break;
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @return bool
     */
    protected function exportDelta(OutputInterface $output)
    {
        $this->exporter->prepareDeltaExport();

        if(!$this->dryRun){
            if(!$this->pushZip($output)){
                return false;
            }
        }

        $output->writeln('<info>Delta entities successfully exported exported</info>');
        return true;
    }

    /**
     * @param OutputInterface $output
     * @return bool
     */
    protected function exportFull(OutputInterface $output)
    {
        $this->exporter->prepareExport();

        if(!$this->dryRun){
            if(!$this->pushZip($output)){
                return false;
            }
        }

        $output->writeln('<info>All entities successfully exported exported</info>');
        return true;
    }

    /**
     * @param OutputInterface $output
     * @return bool
     */
    public function pushZip(OutputInterface $output){

        $response = $this->exporter->pushZip();

        if (array_key_exists('error_type_number', $response)) {
            $output->writeln(sprintf('<error>Exporter exited with the following message: "%s"</error>', $response['message']));
            return false;
        }

        return true;
    }

}