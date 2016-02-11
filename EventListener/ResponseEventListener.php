<?php
/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\EventListener;


use Ibrows\BoxalinoBundle\Helper\HttpP13nService;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseEventListener
{
    /**
     * @var HttpP13nService
     */
    protected $httpP13nService;

    /**
     * @param HttpP13nService $httpP13nService
     */
    public function setHttpP13nService(HttpP13nService $httpP13nService)
    {
        $this->httpP13nService = $httpP13nService;
    }

    /**
     * @param FilterResponseEvent $event
     * @return FilterResponseEvent
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->getRequest()->cookies->get('cems')) {
            $event->getResponse()->headers->setCookie($this->httpP13nService->createCemsCookie());
            $event->getResponse()->headers->setCookie($this->httpP13nService->createCemvCookie());
        }
    }
}