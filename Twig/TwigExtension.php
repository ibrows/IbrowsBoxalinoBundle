<?php
namespace Ibrows\BoxalinoBundle\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Class TwigExtension
 * @package Ibrows\BoxalinoBundle\Twig
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class TwigExtension extends \Twig_Extension
{
    private $account;
    private $requestStack;

    /**
     * Constructor.
     *
     * @param string       $account      The account name
     * @param RequestStack $requestStack The Symfony request stack
     */
    public function __construct($account, RequestStack $requestStack)
    {
        $this->account = $account;
        $this->requestStack = $requestStack;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'ibrows_boxalino_tracker',
                array($this, 'getBoxalinoTracker'),
                array('is_safe' => array('html'), 'needs_environment' => true)),
            new \Twig_SimpleFunction(
                'ibrows_boxalino_search_tracker',
                array($this, 'getBoxalinoSearchTracker'),
                array('is_safe' => array('html'), 'needs_environment' => true)),
            new \Twig_SimpleFunction(
                'ibrows_boxalino_product_view_tracker',
                array($this, 'getBoxalinoProductViewTracker'),
                array('is_safe' => array('html'), 'needs_environment' => true)),
            new \Twig_SimpleFunction(
                'ibrows_boxalino_get_promise',
                array($this, 'getPromise'),
                array('is_safe' => array('html')))
        );
    }

    /**
     * @param \Twig_Environment $env
     * @param array $trackActions
     * @return string
     */
    public function getBoxalinoTracker(\Twig_Environment $env, array $trackActions = array())
    {
        $promises = array();

        foreach ($trackActions as $promise => $params) {
            $promises[] = $this->getPromise($promise, $params);
        }

        return $this->createTrackingScript($env, $promises);
    }

    /**
     * @param \Twig_Environment $env
     * @param $searchTermKey
     * @param null $filterkeys
     * @param bool $standalone
     * @return string
     */
    public function getBoxalinoSearchTracker(\Twig_Environment $env, $searchTermKey, $filterkeys = null, $standalone = false)
    {
        $trackActions = array();
        $params = array();

        if(!is_array($filterkeys)){
            $filterkeys = array($filterkeys);
        }
        $filters = $this->getSearchFilterValues($filterkeys);
        $request = $this->getRequest();
        $searchTerm = $this->escape($env, $request->get($searchTermKey));

        if ($searchTerm) {

            $params = array(
                'searchTerm' => $searchTerm,
                'filters' => $filters,
            );

            $trackActions = array(
                'trackSearch' => $params
            );
        }

        if($standalone === true){
            return $this->getPromise('trackSearch', $params);
        }

        return $this->getBoxalinoTracker($env, $trackActions);

    }

    /**
     * @param array $filterKeys
     * @return array
     */
    protected function getSearchFilterValues(array $filterKeys)
    {
        $request = $this->getRequest();

        $filters = array();
        foreach ($filterKeys as  $filterKey) {
            if (!$filterValue = $request->get($filterKey)) {
                continue;
            }

            if ('categories' === $filterKey) {
                $filterKey = 'hrc_'.$filterKey;
            }

            $filterName = str_replace('products_', '', $filterKey);

            $filters['filter_'.$filterName] = $filterValue;
        }

        return $filters;
    }

    /**
     * @param \Twig_Environment $env
     * @param $product
     * @param bool $standalone
     * @return mixed|string
     */
    public function getBoxalinoProductViewTracker(\Twig_Environment $env, $product, $standalone = false)
    {
        $trackActions = array();
        $params = array();

        if($product){
            $params = array(
                'product' => $product
            );

            $trackActions = array(
                'trackProductView' => $params
            );
        }

        if($standalone == true){
            return $this->getPromise('trackProductView', $params);
        }

        return $this->getBoxalinoTracker($env, $trackActions);
    }

    /**
     * @param $promise
     * @param array $params
     * @return mixed
     */
    public function getPromise($promise, array $params)
    {
        $method = $this->getPromiseName($promise);

        if(!method_exists($this, $method)){
            return '';
        }

        return $this->$method($params);
    }

    /**
     * @param \Twig_Environment $env
     * @param null $promises
     * @return string
     */
    protected function createTrackingScript(\Twig_Environment $env, $promises = null)
    {

        $params = array(
            'promises' => $promises,
            'account' => $this->account
        );

        return $env->render('@IbrowsBoxalino/script.html.twig', $params);
    }

    /**
     * @param array $params
     * @return string
     */
    protected function trackSearch(array $params)
    {
        $script = "_bxq.push(['trackSearch', '" . $params['searchTerm'] . "', " . json_encode($params['filters']) . "])";
        return $script;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function trackProductView(array $params)
    {
        $script = "_bxq.push(['trackProductView', '" . $params['product'] . "'])";
        return $script;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function trackAddToBasket(array $params)
    {
        $product = $params['product'];
        $count = $params['count'];
        $price = $params['price'];
        $currency = $params['currency'];

        $script = "_bxq.push(['trackAddToBasket', '" . $product . "', " . $count . ", " . $price . ", '" . $currency . "']);";
        return $script;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function trackCategoryView(array $params)
    {

        $script = "_bxq.push(['trackCategoryView', '" . $params['categoryId'] . "'])";
        return $script;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function trackLogin(array $params)
    {
        $script = "_bxq.push(['trackLogin', '" . $params['customerId'] . "'])";
        return $script;
    }

    /**
     * @param $env
     * @param $term
     * @return string
     * @throws \Twig_Error_Runtime
     */
    protected function escape($env, $term)
    {
        return twig_escape_filter($env, strip_tags($term), 'html');
    }

    /**
     * @param $promise
     * @return string
     */
    public function getPromiseName($promise)
    {

        if (substr($promise, 0, 5) !== 'track') {
            $promise = 'track_' . $promise;
        }
        return self::camelize($promise);
    }

    /**
     * Camelizes a word. This uses the classify() method and turns the first character to lowercase.
     *
     * @param string $word The word to camelize.
     *
     * @return string The camelized word.
     */
    public static function camelize($word)
    {
        return lcfirst(self::classify($word));
    }

    /**
     * @param $word
     * @return mixed
     */
    public static function classify($word)
    {
        return str_replace(" ", "", ucwords(strtr($word, "_-", "  ")));
    }

    /**
     * @return mixed
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'ibrows_boxalino_extension';
    }
}
