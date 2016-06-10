<?php
namespace Ibrows\BoxalinoBundle\Provider\ORM;

use Doctrine\ORM\EntityManager;
use Ibrows\BoxalinoBundle\Provider\EntityProviderInterface;

/**
 * Class EntityProvider
 * @package Ibrows\BoxalinoBundle\Provider\ORM
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class EntityProvider implements EntityProviderInterface
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
     * @param $entity
     * @return mixed
     */
    public function getEntities($entity)
    {
        return $this->findEntities($entity['class']);

    }

    /**
     * @param $className
     * @return array
     */
    public function findEntities($className)
    {
        $rep = $this->em->getRepository($className);

        return $rep->findAll();

    }
}