<?php
/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Provider\ORM;

use Doctrine\ORM\EntityManager;
use Ibrows\BoxalinoBundle\Provider\EntityProviderInterface;

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
    public function __construct(EntityManager  $em)
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