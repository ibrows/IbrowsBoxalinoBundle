<?php
/**
 * This file is part of the go-do-it  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Tests\Twig;


use Ibrows\BoxalinoBundle\Twig\TwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TwigExtension
     */
    protected static $twigExtension;

    /**
     * @var
     */
    protected static $twigEnvironment;

    public static function setUpBeforeClass()
    {
        self::$twigExtension = new TwigExtension();
        self::$twigEnvironment = new \Twig_Environment();
    }

    public function testSetAccount()
    {
        self::$twigExtension->setAccount('test');

        $this->assertSame('test', self::$twigExtension->getAccount(), 'Account set');
    }

    public function testSetRequest()
    {

        $requestStack = new RequestStack();

        $request = new Request(array('search' => 'searched for term'));
        $requestStack->push($request);

        self::$twigExtension->setRequest($requestStack);

        $this->assertTrue(self::$twigExtension->getRequest() instanceof Request, 'Request object set');

        $searchTerm = self::$twigExtension->getRequest()->get('search');

        $this->assertSame('searched for term', $searchTerm, 'Search term is set on the request object');
    }

    public function testBoxalinoTracker()
    {
        $trackerCode = self::$twigExtension->getBoxalinoTracker(self::$twigEnvironment, 'viewProduct', '2', array
        ('filter' => 1));

        $this->assertSame(150, strpos($trackerCode, '_bxq.push([\'trackPageView\']);'), 'Tracker code created
        successfully');
        $this->assertSame(224, strpos($trackerCode, 'trackViewProduct'), 'Action added Correctly');
        $this->assertSame(248, strpos($trackerCode, '{"filter":1}]'), 'Filter added Correctly');

    }

    public function testBoxalinoSearchTracker()
    {
        $trackerCode = self::$twigExtension->getBoxalinoSearchTracker(self::$twigEnvironment, 'search');

        $this->assertSame(212, strpos($trackerCode, '_bxq.push([\'trackSearch\', \'searched for term\', []]);'),
            'Search tracker code correctly created');

    }

    public function testCamelize()
    {
        $twigExtension = self::$twigExtension;
        $this->assertSame('thisIsCamelized', $twigExtension::camelize('this_is_camelized'), 'Text is camelized');
    }

    public function testClassify()
    {
        $twigExtension = self::$twigExtension;
        $this->assertSame('ThisIsClassified', $twigExtension::classify('this_is_classified'), 'Text is classified');
    }

    public function testGetAction()
    {
        $this->assertSame('trackMyAction', self::$twigExtension->getAction('my_action'), 'Track action correctly created');
    }

    public function testGetFunctions()
    {
        $functions = self::$twigExtension->getFunctions();

        $this->assertSame(2, count($functions), 'Two twig functions available');
    }

    public function testGetName()
    {
        $this->assertSame('ibrows_boxalino_extension', self::$twigExtension->getName(), 'Extension name correct');

    }
}