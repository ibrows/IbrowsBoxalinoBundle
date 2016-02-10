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
class ExporterCommand extends ContainerAwareCommand
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
        $this->setName('ibrows:boxalino:export')
            ->setDescription('Export configured entities to boxalino')
            ->addOption('sync', null, InputOption::VALUE_REQUIRED, 'Sync strategy, possible values are full, delta, partial, properties', 'full')
            ->addOption('entities', null, InputOption::VALUE_OPTIONAL| InputOption::VALUE_IS_ARRAY, 'Array of entities for partial sync only')
            ->addOption('properties-xml', null, InputOption::VALUE_OPTIONAL, 'Path to properties xml file will override configured file')
            ->addOption('publish', null, InputOption::VALUE_NONE, 'If the Export should be published')
            ->setHelp(<<<EOT
The <info>%command.name%</info> exports the properties xml, and csv files to boxalino:

    <info>php %command.full_name%</info>

The <info>--sync</info> should be set to full, delta, partial or properties

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

        $syncType = $input->getOption('sync');

        if (!in_array($syncType, $this->syncStrategies)) {
            $output->writeln(sprintf('<error>Sync stategy %s is not supported, possible options are full, delta and partial</error>', $syncType));
            return 1;
        }
        $this->exporter = $this->getContainer()->get('ibrows_boxalino.exporter.exporter');

        if ($input->getOption('publish')) {
            $this->exporter->setDebugMode(false);
        }

        $response = null;
        $style = 'info';

        switch ($syncType) {
            case self::SYNC_PROPERTIES:
                $response = $this->exportProperties($input->getOption('properties-xml'));
                break;
            case self::SYNC_FULL:
                $response = $this->exportFull();
                break;
            case self::SYNC_DELTA:
                $response = $this->exportDelta();
                break;
            case self::SYNC_PARTIAL:
                try {
                    $this->validateEntities($input->getOption('entities'));
                } catch (\Exception $e) {
                    $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
                    return 1;
                }

                foreach ($input->getOption('entities') as $name) {
                    $response = $this->exportPartial($name);
                }
                break;
        }

        if(array_key_exists('error_type_number', $response)){
            $style = 'error';
        }

        $output->writeln(sprintf('<%1$s>Exporter exited with the following error: "%2$s"</%1$s>', $style,
            $response['message']));


        return 0;
    }

    /**
     * @param $entities
     * @throws \Exception
     */
    protected function validateEntities($entities){

        if(empty($entities)){
            throw new \Exception('Please provide which entities you would like to sync by key');
        }

        $ibrowsBoxalinoEntities = $this->getContainer()->getParameter('ibrows_boxalino.entities');
        foreach ($entities as $entity) {
            if(!array_key_exists($entity, $ibrowsBoxalinoEntities)){
                throw new \Exception(sprintf('The entity %s is not configured to by synced to boxalino', $entity));
            }
        }
    }

    /**
     * @return string
     */
    protected function exportDelta()
    {
        $this->exporter->prepareDeltaExport();

        return $this->exporter->pushZip();
    }

    /**
     * @return string
     */
    protected function exportFull()
    {
        $this->exporter->prepareFullExport();

        return $this->exporter->pushZip();
    }

    /**
     * Not yet supported
     * @param $name
     * @return string
     */
    protected function exportPartial($name)
    {
//        $this->exporter->preparePartialExport($name);
//
//        return $this->exporter->pushZip();
    }

    /**
     * @param null $propertiesXml
     * @return string
     */
    protected function exportProperties($propertiesXml = null)
    {
        if($propertiesXml){
            $this->exporter->setPropertiesXml($propertiesXml);
        }
        return $this->exporter->pushXml();
    }
}