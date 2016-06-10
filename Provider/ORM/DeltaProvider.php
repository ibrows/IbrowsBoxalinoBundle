<?php
namespace Ibrows\BoxalinoBundle\Provider\ORM;


use Doctrine\ORM\EntityManager;
use Ibrows\BoxalinoBundle\Model\DeltaTimestampableInterface;
use Ibrows\BoxalinoBundle\Provider\DeltaProviderInterface;

/**
 * Class DeltaProvider
 * @package Ibrows\BoxalinoBundle\Provider\ORM
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class DeltaProvider implements DeltaProviderInterface
{

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * EntityProvider constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param \DateTime $dateTime
     * @param $className
     * @param $strategy
     * @param $strategyOptions
     * @return array|mixed
     */
    public function getDeltaEntities(\DateTime $dateTime, $className, $strategy, $strategyOptions)
    {
        if ($strategy === 'repositoryMethod') {
            return $this->getFromRepositoryMethod($dateTime, $className, $strategyOptions);
        }

        if ($strategy === 'timestambleFieldQuery') {
            return $this->getFromQueryField($dateTime, $className, $strategyOptions);
        }

        if ($strategy === 'fromFullData') {
            return $this->getFromFullData($dateTime, $className);
        }

        return array();
    }

    /**
     * @param \DateTime $dateTime
     * @param $className
     * @param array $strategyOptions
     * @return mixed
     */
    public function getFromRepositoryMethod(\DateTime $dateTime, $className, array $strategyOptions)
    {
        $method = $strategyOptions['repository_method'];
        return $this->em->getRepository($className)->$method($dateTime);
    }

    /**
     * @param \DateTime $dateTime
     * @param $className
     * @param array $strategyOptions
     * @return mixed
     */
    public function getFromQueryField(\DateTime $dateTime, $className, array $strategyOptions)
    {
        $qb = $this->em->getRepository($className)->createQueryBuilder('e');

        $qb->where(sprintf('e.%s > :lastExport', $strategyOptions['timestampable_query_field']));

        $result = $qb->getQuery()->execute(array(':lastExport' => $dateTime));
        return $result;
    }

    /**
     * @param \DateTime $dateTime
     * @param $className
     * @return array
     */
    public function getFromFullData(\DateTime $dateTime, $className)
    {
        $filteredEntities = array();
        $entities = $this->em->getRepository($className)->findAll();

        foreach ($entities as $entity) {
            if ($entity instanceof DeltaTimestampableInterface) {
                if ($entity->getDeltaTimestamp()->getTimestamp() > $dateTime->getTimestamp()) {
                    $filteredEntities[] = $entity;
                }
            } else {
                $filteredEntities[] = $entity;
            }
        }

        return $filteredEntities;
    }
}