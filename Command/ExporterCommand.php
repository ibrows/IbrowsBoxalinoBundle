<?php
namespace Ibrows\BoxalinoBundle\Command;

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
class ExporterCommand extends ContainerAwareCommand
{
    const SYNC_FULL = 'full';
    const SYNC_DELTA = 'delta';
    const SYNC_PARTIAL = 'partial';

    protected $syncStrategies = array('full', 'delta', 'partial');


    /**
     * configuration for the command
     */
    protected function configure()
    {
        $this->setName('ibrows:boxalino:export')
            ->setDescription('Export configured entities to boxalino')
            ->addOption('sync', null, InputOption::VALUE_REQUIRED, 'Sync strategy, possible values are full, delta, and partial', 'full')
            ->addOption('entities', null, InputOption::VALUE_OPTIONAL| InputOption::VALUE_IS_ARRAY, 'Array of entities for partial sync only');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $syncType = $input->getOption('sync');

        if(!in_array($syncType, $this->syncStrategies)){
            $output->writeln(sprintf('<error>Sync stategy %s is not supported, possible options are full, delta and partial</error>', $syncType));
            return 1;
        }

        switch($syncType){
            case self::SYNC_FULL:
                $this->exportFull();
                break;
            case self::SYNC_DELTA:
                break;
            case self::SYNC_PARTIAL:
                try{
                    $this->validateEntities($input->getOption('entities'));
                }catch (\Exception $e){
                    $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
                    return 1;
                }
                break;
        }
        return  0;
    }

    /**
     * @param $entities
     * @throws \Exception
     */
    protected function validateEntities($entities){

        if(empty($entities)){
            throw new \Exception('Please provide which entities you would like to sync, either by key, or full namespace');
        }
        if(!is_array($entities)){
            $entities = array($entities);
        }
        $ibrowsBoxalinoEntities = $this->getContainer()->getParameter('ibrows_boxalino.entities');

        foreach ($entities as $entity) {
            if(!in_array($entity, $ibrowsBoxalinoEntities) || !array_key_exists($entity, $ibrowsBoxalinoEntities)){
                throw new \Exception(sprintf('The entity %s is not configured to by synced to boxalino', $entity));
            }
        }
    }

    protected function exportFull()
    {
        $exporter = $this->getContainer()->get('ibrows_boxalino.exporter.exporter');

        $exporter->exportFull();
    }
}