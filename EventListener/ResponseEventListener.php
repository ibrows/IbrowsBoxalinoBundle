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


use Ibrows\BoxalinoBundle\Helper\HttpP13nHelper;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

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
        if (!$cems = $event->getRequest()->cookies->get('cems')) {
            $cems = $this->httpP13nService->createCemsCookie();
        }

        if (!$cemv = $event->getRequest()->cookies->get('cemv')) {
            $cemv = $this->httpP13nService->createCemvCookie();
        }

        $event->getResponse()->headers->setCookie($cems);
        $event->getResponse()->headers->setCookie($cemv);
    }
}