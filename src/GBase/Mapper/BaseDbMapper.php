<?php

namespace GBase\Mapper;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Where;
use Zend\Filter\Word\SeparatorToCamelCase;
use ZfcBase\Mapper\AbstractDbMapper;

Class BaseDbMapper extends AbstractDbMapper
{

    protected $tableName = null;
    protected $id = null;

    /**
     * PrimaryKey Table  
     * @return string
     */
    public function getId()
    {
        /*if (null === $this->id) {
            
        }*/

        return $this->id;
    }

    public function getTableName()
    {
        return parent::getTableName();
    }
    
    public function findById($id)
    {
        $select = new Select;
        $select->from($this->getTableName());

        $where = new Where;
        $where->equalTo($this->getId(), $id);


        $result = $this->select($select->where($where))->current();
        return $result;
    }

    /**
     * 
     * @param object $entity
     * @return object
     */
    public function persist($entity)
    {
        $getEntityId = $this->entityMethod($this->getId(), 'get');
        $setEntityId = $this->entityMethod($this->getId(), 'set');

        if ($entity->$getEntityId() > 0) {
            $where = new Where;
            $where->equalTo($this->getId(), $entity->$getEntityId());
            $this->update($entity, $where, $this->getTableName());
        } else {
            $result = $this->insert($entity, $this->getTableName());
            $entity->$setEntityId($result->getGeneratedValue());
        }

        return $entity;
    }


    /**
     * $attr Attribute to getter and setter
     * $type get or set
     * 
     * @param string $attr
     * @param string $type
     * @return string
     */
    protected function entityMethod($attr, $type = null)
    {
        $filter = new SeparatorToCamelCase('_');
        $method = $filter->filter($attr);
        return $type . $method;
    }

}

