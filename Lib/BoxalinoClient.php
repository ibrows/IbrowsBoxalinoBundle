<?php
/**
 * This file is part of the boxalinosandbox  package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\BoxalinoBundle\Lib;

use com\boxalino\bxclient\v1\BxClient;

/**
 * Class BxClient
 * @package Ibrows\BoxalinoBundle\Lib
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class BoxalinoClient extends BxClient
{


    /**
     * @var
     */
    protected $sessionId;
    /**
     * @var
     */
    protected $profileId;

    /**
     * @return mixed
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param mixed $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @return mixed
     */
    public function getProfileId()
    {
        return $this->profileId;
    }

    /**
     * @param mixed $profileId
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;
    }


    /**
     * @return array
     */
    protected function getSessionAndProfile() {
        return array($this->sessionId, $this->profileId);
    }
}