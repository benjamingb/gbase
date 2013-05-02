<?php

namespace GBase\Service;

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\Stdlib\Hydrator\ClassMethods;
use ZfcBase\EventManager\EventProvider;

class BaseService extends EventProvider implements EventManagerAwareInterface
{

    /**
     * @var Mapper 
     */
    protected $mapper;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    public function __construct()
    {
        $this->setEventManager(new EventManager());
    }

    public function findById($id)
    {
        return $this->mapper->findById($id);
    }

    public function fetchPairs($cols, $where = null)
    {
        return $this->mapper->fetchPairs($cols, $where);
    }
    
    public function persist($entity)
    {
        if (is_array($entity)) {
            $hydrator = new ClassMethods;
            $entity = $hydrator->hydrate($entity, $this->getMapper()->getEntityPrototype());
        }

        $entity = $this->mapper->persist($entity);

        $events = $this->getEventManager();
        $events->trigger(get_class($this->getMapper()->getEntityPrototype()) . ".post", $this, array(
            $this->getMapper()->getTableName() => $entity
        ));
        return $entity;
    }

    public function setMapper($mapper)
    {
        $this->mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * Retrieve service manager instance
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Set service manager instance
     *
     * @param ServiceManager $serviceManager
     * @return User
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }

}