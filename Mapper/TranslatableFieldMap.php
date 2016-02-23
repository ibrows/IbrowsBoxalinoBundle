<?php
/**
 * This file is part of the schuler-shop  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Mapper;

use Gedmo\Translatable\Mapping\Event\TranslatableAdapter;

class TranslatableFieldMap extends FieldMap
{
    /**
     * @var
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
     * @var
     */
    protected $class;

    /**
     * @return mixed
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
     * @return mixed
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
     * @return mixed
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