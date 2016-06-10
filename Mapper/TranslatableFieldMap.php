<?php
namespace Ibrows\BoxalinoBundle\Mapper;

use Gedmo\Translatable\Mapping\Event\TranslatableAdapter;

/**
 * Class TranslatableFieldMap
 * @package Ibrows\BoxalinoBundle\Mapper
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class TranslatableFieldMap extends FieldMap
{
    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $translatableClass;

    /**
     * @var TranslatableAdapter
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $class;

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @param TranslatableAdapter $adapter
     * @return $this
     */
    public function setAdapter(TranslatableAdapter $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @return TranslatableAdapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @return string
     */
    public function getTranslatableClass()
    {
        return $this->translatableClass;
    }

    /**
     * @param $translatableClass
     * @return $this
     */
    public function setTranslatableClass($translatableClass)
    {
        $this->translatableClass = $translatableClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param $class
     * @return $this
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }



}