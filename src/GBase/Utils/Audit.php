<?php

namespace GBase\Utils;

use Zend\Session\Container;
use Zend\ServiceManager\ServiceManager;

class Audit
{

    protected $serviceManager;

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    public function getServer()
    {
        return $this->getServiceManager()->get('request')->getServer();
    }

    public function getEnvironment()
    {
        $appUser = new Container('AppUser');

        $audit = [
            'session'     => [
                'date'      => date('Y-m-d H:i:s'),
                'id'        => $appUser->Id,
                'approl'    => $appUser->approl,
                'nombres'   => $appUser->nombres,
                'apellidos' => $appUser->apellidos,
            ],
            'environment' => [
                'REMOTE_ADDR'     => $this->getServer()->REMOTE_ADDR,
                'HTTP_USER_AGENT' => $this->getServer()->HTTP_USER_AGENT,
                'HTTP_REFERER'    => $this->getServer()->HTTP_REFERER,
                'HTTP_COOKIE'     => $this->getServer()->HTTP_COOKIE,
                'REDIRECT_URL'    => $this->getServer()->REDIRECT_URL,
                'REQUEST_URI'     => $this->getServer()->REQUEST_URI,
            ]
        ];

        return $audit;
    }

}
