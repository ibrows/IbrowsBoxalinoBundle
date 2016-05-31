<?php
namespace Ibrows\BoxalinoBundle\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class TwigExtension extends \Twig_Extension
{
    /**
     * @var
     */
    private $account;

    /**
     * @var Request
     */
    private $request;

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
                array('is_safe' => array('html'),'needs_environment' => true))
        );
    }

    /**
     * @param \Twig_Environment $env
     * @param null $action
     * @param null $value
     * @param array $args
     * @return string
     */
    public function getBoxalinoTracker(\Twig_Environment $env, $action = null, $value = null, array $args = array())
    {
        $push = '';

        $value = $this->escape($env, $value);

        if (!is_null($action) && !is_null($value)) {
            $push = $this->createPush($action, $value, $args);
        }
        return <<<SCRIPT
            <script type="text/javascript">
                var _bxq = _bxq || [];
                _bxq.push(['setAccount', '$this->account']);
                _bxq.push(['trackPageView']);
                $push
                (function(){
                    var s = document.createElement('script');
                    s.async = 1;
                    s.src = '//cdn.bx-cloud.com/frontend/rc/js/ba.min.js';
                    document.getElementsByTagName('head')[0].appendChild(s);
                 })();
            </script>
SCRIPT;
    }

    /**
     * @param \Twig_Environment $env
     * @param $searchTermKey
     * @param null $filterKey
     * @return string
     * @throws \Twig_Error_Runtime
     */
    public function getBoxalinoSearchTracker(\Twig_Environment $env, $searchTermKey, $filterKey = null){

        $args = $filterKey ? $this->request->get($filterKey, array()): array();
        return $this->getBoxalinoTracker($env, 'trackSearch',$this->request->get($searchTermKey), $args);
    }

    /**
     * @param $env
     * @param $term
     * @return string
     * @throws \Twig_Error_Runtime
     */
    public function escape($env, $term)
    {
        return twig_escape_filter($env,strip_tags($term), 'html');
    }

    /**
     * @param $action
     * @param $value
     * @param array $args
     * @return string
     */
    private function createPush($action, $value, $args = array())
    {
        $action = $this->getAction($action);

        $filters = json_encode($args);
        $script = <<<SCRIPT
                _bxq.push(['$action', '$value', $filters]);
SCRIPT;
        return $script;
    }

    /**
     * @param $action
     * @return string
     */
    public function getAction($action){

        if(substr($action, 0, 5) !== 'track'){
            $action = 'track_'.$action;
        }
        return self::camelize($action);
    }

    public static function classify($word)
    {
        return str_replace(" ", "", ucwords(strtr($word, "_-", "  ")));
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
     * @param $account
     * @return $this
     */
    public function setAccount($account)
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param RequestStack $requestStack
     * @return $this
     */
    public function setRequest(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
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