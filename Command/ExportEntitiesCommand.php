<?php
namespace Ibrows\BoxalinoBundle\Command;

use Ibrows\BoxalinoBundle\Exporter\Exporter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) iBrows <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class ExportEntitiesCommand extends ContainerAwareCommand
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
        $this->setName('ibrows:boxalino:export-entities')
            ->setDescription('Export configured entities to boxalino')
            ->addOption('sync', null, InputOption::VALUE_REQUIRED, 'Sync strategy, possible values are full, delta, partial, properties', 'full')
//            ->addOption('entities', null, InputOption::VALUE_OPTIONAL| InputOption::VALUE_IS_ARRAY, 'Array of entities for partial sync only')
            ->addOption('push-live', null, InputOption::VALUE_NONE, 'If the Export should be pushed to the Live index, otherwise it is always pushed to dev')
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

        switch ($syncType) {
            case self::SYNC_FULL:
                $this->exportFull($output);
                break;
            case self::SYNC_DELTA:
                $this->exportDelta($output);
                break;
//            case self::SYNC_PARTIAL:
//                try {
//                    $this->validateEntities($input->getOption('entities'));
//                } catch (\Exception $e) {
//                    $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
//                    return 1;
//                }
//
//                foreach ($input->getOption('entities') as $name) {
//                    $response = $this->exportPartial($name);
//                }
//                break;
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

        $response = $this->exporter->pushZip();

        if (array_key_exists('error_type_number', $response)) {
            $output->writeln(sprintf('<error>Exporter exited with the following message: "%s"</error>', $response['message']));
            return false;
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

        $response = $this->exporter->pushZip();
        
        if (array_key_exists('error_type_number', $response)) {
            $output->writeln(sprintf('<error>Exporter exited with the following message: "%s"</error>', $response['message']));
            return false;
        }
        $output->writeln('<info>All entities successfully exported exported</info>');
        return true;
    }

    /**
     * @param $entities
     * @throws \Exception
     */
//    protected function validateEntities($entities){
//
//        if(empty($entities)){
//            throw new \Exception('Please provide which entities you would like to sync by key');
//        }
//
//        $ibrowsBoxalinoEntities = $this->getContainer()->getParameter('ibrows_boxalino.entities');
//        foreach ($entities as $entity) {
//            if(!array_key_exists($entity, $ibrowsBoxalinoEntities)){
//                throw new \Exception(sprintf('The entity %s is not configured to by synced to boxalino', $entity));
//            }
//        }
//    }

    /**
     * Not yet supported
     * @Todo: check if we want to keep this
     * @param $name
     * @return string
     */
//    protected function exportPartial($name)
//    {
//        $this->exporter->preparePartialExport($name);
//
//        return $this->exporter->pushZip();
//    }

}