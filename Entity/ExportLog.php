<?php

namespace Ibrows\BoxalinoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExportLog
 *
 * @ORM\Table(name="export_log")
 * @ORM\Entity()
 */
class ExportLog
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $entity;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $executedAt;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $type;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set entity
     *
     * @param string $entity
     * @return ExportLog
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity
     *
     * @return string 
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set exportData
     *
     * @param \DateTime $executedAt
     * @return ExportLog
     */
    public function setExecutedAt($executedAt)
    {
        $this->executedAt = $executedAt;

        return $this;
    }

    /**
     * Get exportData
     *
     * @return \DateTime 
     */
    public function getExecutedAt()
    {
        return $this->executedAt;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return ExportLog
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }
}
