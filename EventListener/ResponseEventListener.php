<?php

namespace Ibrows\BoxalinoBundle\EventListener;


use Ibrows\BoxalinoBundle\Helper\HttpP13nHelper;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class ResponseEventListener
 * @package Ibrows\BoxalinoBundle\EventListener
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class ResponseEventListener
{
    /**
     * @var HttpP13nHelper
     */
    protected $httpP13nService;

    /**
     * @param HttpP13nHelper $httpP13nHelper
     */
    public function setHttpP13nHelper(HttpP13nHelper $httpP13nHelper)
    {
        $this->httpP13nService = $httpP13nHelper;
    }

    /**
     * @param FilterResponseEvent $event
     * @return FilterResponseEvent
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $cems = $this->httpP13nService->getCemsCookie($event->getRequest());
        $cemv = $this->httpP13nService->getCemvCookie($event->getRequest());

        $event->getResponse()->headers->setCookie($cems);
        $event->getResponse()->headers->setCookie($cemv);
    }
}