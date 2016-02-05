<?php
/**
 * This file is part of the go-do-it  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Tests\Fixtures;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Ibrows\BoxalinoBundle\Tests\Entity\Brand;
use Ibrows\BoxalinoBundle\Tests\Entity\Product;
use Ibrows\BoxalinoBundle\Tests\Entity\ProductCategory;

class LoadTestData extends AbstractFixture
{

    private static $products = array(
        array(
            'name' => 'iPhone',
            'price' => '860.00',
            'brand' => 'apple',
            'categories' => array('mobilephones', 'electronic')
        ),
        array(
            'name' => 'Galaxy s5',
            'price' => '750.00',
            'brand' => 'samsung',
            'categories' => array('mobilephones', 'electronic')
        ),
        array(
            'name' => 'Vac 500',
            'price' => '430.00',
            'brand' => 'electrolux',
            'categories' => array('appliance', 'electronic')
        ),
    );

    private static $brands = array('apple', 'samsung', 'electrolux');


    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $categoryProducts = array();
        $brands = array();
        foreach (self::$brands as $brandName) {
            $brand = new Brand();
            $brand->setName($brandName);

            $manager->persist($brand);

            $brands[$brandName] = $brand;
        }

        foreach (self::$products as $productConfig) {

            $product = new Product();

            $product
                ->setName($productConfig['name'])
                ->setPrice($productConfig['price'])
                ->setBrand($brands[$productConfig['brand']])
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime())
                ->setDescription('this is blah blah');

            $manager->persist($product);


            foreach ($productConfig['categories'] as $category) {
                $categoryProducts[$category][] = $product;
            }

        }

        foreach ($categoryProducts as $category => $products) {
            $productCategory = new ProductCategory();
            $products = new ArrayCollection($products);
            $productCategory->setProducts($products);


            $productCategory->setName($category)
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime())
                ->setDescription('this is blah blah');
            $manager->persist($productCategory);
        }
        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 1;
    }
}