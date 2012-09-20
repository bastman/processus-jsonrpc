<?php

namespace Processus\Lib\JsonRpc2;

class Auth implements \Processus\Interfaces\InterfaceAuthModule
{
    /**
     * @var bool
     */
    private $_isAuthorized = true;

    /**
     * @return bool
     */
    public function isAuthorized()
    {
        return $this->_isAuthorized;
    }

    public function setAuthData($authData)
    {
        return true;
    }
}
