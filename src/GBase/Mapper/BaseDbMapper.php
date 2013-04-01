<?php

namespace GBase\Mapper;

use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Where;
use Zend\Filter\Word\SeparatorToCamelCase;
use ZfcBase\Mapper\AbstractDbMapper;

Class BaseDbMapper extends AbstractDbMapper
{

    protected $tableName = null;
    protected $id = null;

    //protected ss;

    /**
     * PrimaryKey Table  
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function findById($id)
    {
        $select = $this->getSelect()
                ->where(array($this->getId() => $id));

        $result = $this->select($select)->current();
        return $result;
    }

    public function persist($entity)
    {
        $getEntityId = $this->entityMethod($this->getId(), 'get');
        $setEntityId = $this->entityMethod($this->getId(), 'set');

        if ($entity->$idEntity() > 0) {
            $where = new Where;
            $where->equalTo($this->getId(), $entity->$getEntityId());
            $this->update($entity, $where, $this->getTableName());
        } else {
            $result = $this->insert($entity, $this->getTableName());
            $address->$setEntityId($result->getGeneratedValue());
        }

        return $entity;
    }

    /* public function persist($entity)
      {
      $result = $this->insert($entity, $this->getTableName());
      //$entity->setInvestigadorId($result->getGeneratedValue());
      return $result;
      } */

    protected function entityMethod($attr, $type = null)
    {
        $filter = new SeparatorToCamelCase('_');
        $method = $filter->filter($attr);
        return $type . $method;
    }

}

