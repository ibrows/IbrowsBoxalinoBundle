<?php

namespace Ibrows\BoxalinoBundle\Entity;


use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Ibrows\BoxalinoBundle\Model\ExportLogManagerInterface;

/**
 * Class ExportLogManager
 * @package Ibrows\BoxalinoBundle\Entity
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class ExportLogManager implements ExportLogManagerInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param $className
     * @param $type
     * @return bool
     * @throws \Exception
     */
    public function createLogEntry($className, $type)
    {
        $exportLog = new ExportLog();
        $exportLog->setEntity($className)
            ->setType($type)
            ->setExecutedAt(new \DateTime());
        return $this->save($exportLog);
    }

    /**
     * @param $entity
     * @return \DateTime|null
     */
    public function getLastExportDateTime($entity)
    {
        $exportLog =  $this->findOneBy(array('entity' => $entity), array('executedAt' => 'DESC'));

        if(!$exportLog instanceof ExportLog){
            return null;
        }

        return $exportLog->getExecutedAt();

    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @return ExportLog
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        return $this->em->getRepository('IbrowsBoxalinoBundle:ExportLog')->findOneBy($criteria, $orderBy);
    }

    /**
     * @param $entity
     * @param bool $andFlush
     * @return bool
     * @throws \Exception
     */
    public function save($entity, $andFlush = true)
    {
        try{
            $this->em->persist($entity);

            if ($andFlush) {
                $this->em->flush();
            }
            return true;
        }catch (\Exception $e){
            throw new \Exception('Failed to save log entry', null, $e);
        }
    }
}