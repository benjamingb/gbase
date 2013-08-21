<?php

/**
 * GnBit  (http://gnbit.com/)
 * 
 * @author: Benjamin Gonzales (benjamin@gnbit.com)
 * @Copyright (c) 2013 GnBit.SAC - http://www.gnbit.com
 * 
 */

namespace GBase\Mapper;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Where;
use Zend\Db\ResultSet\ResultSet;
use Zend\Filter\Word\SeparatorToCamelCase;
use ZfcBase\Mapper\AbstractDbMapper;
use Zend\Stdlib\Hydrator\HydratorInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\Hydrator\ClassMethods;

Class BaseDbMapper extends AbstractDbMapper
{

    /**
     * Tbale Nane
     * @var string 
     */
    protected $tableName = null;

    /**
     * Id table Database
     * 
     * @var string | int 
     */
    protected $id = null;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * PrimaryKey Table  
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function getTableName()
    {
        return parent::getTableName();
    }

    /**
     * 
     * exaample 
     * 
     * $where = function(Select $select) {
     *              $select->where(array('area_id' => 'A'))->order("facultad");
     *         };
     * 
     *  
     * @param array |  \GBase\Mapper\Closure $cols
     * @param \GBase\Mapper\Closure $where
     * @return type
     */
    public function fetchPairs($cols, $where = null)
    {

        $select = new Select;
        $select->from($this->getTableName());

        if ($cols instanceof \Closure) {
            $cols($select);
        } elseif (is_array($cols) && count($cols) > 1) {
            $select->columns($cols);
        } else {
            return array();
        }

        if ($where instanceof \Closure) {
            $where($select);
        } elseif ($where !== null) {
            $select->where($where);
        }

        $statement = $this->getSql()->prepareStatementForSqlObject($select);

        $rowset = $statement->execute();

        $columns = $select->getRawState('columns');

        $key = array_keys($columns);
        $value = array_values($columns);

        $assoc = array();
        foreach ($rowset as $row) {
            $akey = isset($row[$key[0]]) ? $row[$key[0]] : $row[$value[0]];
            $aValue = isset($row[$key[1]]) ? $row[$key[1]] : $row[$value[1]];
            $assoc[$akey] = $aValue;
        }

        return $assoc;
    }

    /**
     * find row by Id 
     * 
     * @param type $id
     * @return type
     */
    public function findById($id, $toArray = false)
    {
        $select = new Select;
        $select->from($this->getTableName());

        $where = new Where;
        $where->equalTo($this->getId(), $id);


        if (!$toArray) {
            $result = $this->select($select->where($where))->current();
            return $result;
        }

        $result = $this->select($select->where($where))->toArray();
        return $result[0];
    }

    /**
     * Retorna los resultado en un Resulset
     * @param \Zend\Db\Sql\Select $select
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function executeResultSet(Select $select)
    {
        $statement = $this->getSql()->prepareStatementForSqlObject($select);
        $rowset = $statement->execute();

        $resultSet = new ResultSet();
        $resultSet->initialize($rowset);
        return $resultSet;
    }

    /**
     * @param object|array $entity
     * @param string|array|closure $where
     * @param string|TableIdentifier|null $tableName
     * @param HydratorInterface|null $hydrator
     * @return ResultInterface
     */
    protected function update($entity, $where, $tableName = null, HydratorInterface $hydrator = null)
    {
        $this->initialize();
        $tableName = $tableName ? : $this->tableName;

        $sql = $this->getSql()->setTable($tableName);
        $update = $sql->update();

        $rowData = $this->entityToArray($entity, $hydrator);

        //$rowData = array_filter($rowData, 'strlen');// quita todos los nulos y vacios
        $rowData = array_filter($rowData, function($element) {
                    return !is_null($element); //retorna solo aquellos que no son null;
                });

        unset($rowData['created_at']); //quita el campo

        $update->set($rowData)
                ->where($where);

        $statement = $sql->prepareStatementForSqlObject($update);

        return $statement->execute();
    }

    /**
     * @param string|array|closure $where
     * @param string|TableIdentifier|null $tableName
     * @return ResultInterface
     */
    public function delete($where, $tableName = null)
    {
        parent::delete($where, $tableName = null);
    }

    /**
     * 
     * @param object $entity
     * @return object
     */
    public function persist($entity)
    {
        
       
        if (is_array($entity)) {
            $hydrator = new ClassMethods;
            $entity = $hydrator->hydrate($entity, $this->getEntityPrototype());
        }

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

    /**
     * Select table 
     * 
     * @return \Zend\Db\Sql\Select
     */
    protected function getSelectAlias()
    {
        $select = new Select;
        $select->from(array('t1' => $this->getTableName()));
        return $select;
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

