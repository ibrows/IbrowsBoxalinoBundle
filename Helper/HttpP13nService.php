<?php
namespace Ibrows\BoxalinoBundle\Helper;

use com\boxalino\p13n\api\thrift\ChoiceRequest;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Thrift\HttpP13n;

/**
 * Class HttpP13nService
 * @package Ibrows\BoxalinoBundle\Client
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class HttpP13nService
{
    /**
     * @var HttpP13n
     */
    protected $client;

    /**
     * @var
     */
    protected $host;

    /**
     * @var
     */
    protected $account;

    /**
     * @var
     */
    protected $username;

    /**
     * @var
     */
    protected $password;

    /**
     * @var
     */
    protected $cookieDomain;

    /**
     * HttpP13nService constructor.
     * @param $host
     * @param $account
     * @param $username
     * @param $password
     * @param $cookieDomain
     */
    public function __construct($host, $account, $username, $password, $cookieDomain)
    {
        $this->host = $host;
        $this->account = $account;
        $this->username = $username;
        $this->password = $password;
        $this->cookieDomain = $cookieDomain;
    }

    /**
     * @return ChoiceRequest
     */
    public function getChoiceRequest()
    {
        $choiceRequest = $this->getClient()->getChoiceRequest($this->account, $this->cookieDomain);

        return $choiceRequest;
    }

    /**
     * @return HttpP13n
     */
    public function getClient()
    {

        if (!$this->client) {
            $this->client = new HttpP13n();
            $this->client->setHost($this->host);
            $this->client->setAuthorization($this->username, $this->password);
        }

        return $this->client;
    }

    /**
     * @param ChoiceRequest $choiceRequest
     * @return \com\boxalino\p13n\api\thrift\ChoiceResponse
     */
    public function choose(ChoiceRequest $choiceRequest){
        return $this->getClient()->choose($choiceRequest);
    }


    /**
     * @return string
     */
    public function generateVistorId(){
        $profileid = '';
        if (function_exists('openssl_random_pseudo_bytes')) {
            $profileid = bin2hex(openssl_random_pseudo_bytes(16));
        }
        if (empty($profileid)) {
            $profileid = uniqid('', true);
        }

        return $profileid;
    }

    /**
     * @return Cookie
     */
    public function createCemvCookie()
    {
        return new Cookie('cemv', $this->generateVistorId(), new \DateTime('+3 months'), '/', null, false, false);
    }

    /**
     * @param SessionInterface $session
     * @return Cookie
     */
    public function createCemsCookie(SessionInterface $session){
        return new Cookie('cems', md5($session->getId()), 0, '/', null, false, false);
    }


}