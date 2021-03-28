<?php

namespace Ibrows\BoxalinoBundle\Lib;

use com\boxalino\bxclient\v1\BxClient;

class BoxalinoClient extends BxClient
{
    protected $sessionId;

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

    public function resetResponses()
    {
        $this->chooseResponses = array();
    }

    /**
     * @return array
     */
    protected function getSessionAndProfile()
    {
        return array($this->sessionId, $this->profileId);
    }
}