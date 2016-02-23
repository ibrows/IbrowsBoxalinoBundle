<?php
/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Entity;


use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Ibrows\BoxalinoBundle\Model\ExportLogManagerInterface;

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

    public function createLogEntry($className, $type)
    {
        $exportLog = new ExportLog();
        $exportLog->setEntity($className)
            ->setType($type)
            ->setExecutedAt(new \DateTime());
        $this->save($exportLog);
    }

    public function getLastExportDateTime($entity)
    {
        $exportLog =  $this->findOneBy(array('entity' => $entity), array('executedAt' => 'DESC'));

        if(!$exportLog instanceof ExportLog){
            return null;
        }

        return $exportLog->getExecutedAt();

    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        return $this->em->getRepository('IbrowsBoxalinoBundle:ExportLog')->findOneBy($criteria, $orderBy);
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, $andFlush = true)
    {

        $this->em->persist($entity);

        if ($andFlush) {
            $this->em->flush();
        }
    }
}