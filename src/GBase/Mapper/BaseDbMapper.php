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
     * Ignore cols table in update table
     * @var array
     */
    protected $ignoreColsUpdate = array(
        'created_at',
    );

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

        $rowset = $this->executeResultSet($select)->toArray();

        $assoc = array();
        foreach ($rowset as $row) {
            reset($row);
            $key         = current($row);
            next($row);
            $val         = current($row) ? current($row) : $key;
            $assoc[$key] = $val;
        }
        return $assoc;
    }

    /**
     * base find row
     * 
     * @param  string $id [description]
     * @return object     [description]
     */
    protected function findByIdRow($id)
    {
        $select = new Select;
        $select->from($this->getTableName());
        $select->where->equalTo($this->getId(), $id);
        $result = $this->select($select);
        return $result;
    }

    /**
     * find row by Id and return entity 
     * 
     * @param type $id
     * @return object 
     */
    public function findById($id)
    {
        return $this->findByIdRow($id)->current();
    }

    /**
     * Find row a return arryas 
     * @param  $id 
     * @return array 
     */
    public function findByIdArray($id)
    {
        $result = $this->findByIdRow($id)->toArray();
        return $result ? $result[0] : array();
    }

    /**
     *  Tratra de que sea el standar, reemplzará a findById y findByIdArray
     * 
     * @param type $id
     * @return type
     */
    public function _findById($id)
    {
        $select = $this->getSelectAlias();
        $select->where->equalTo($this->getId(), $id);
        return $this->executeResultSet($select);
    }

    /**
     * Retorna los resultado en un Resulset
     * @param \Zend\Db\Sql\Select $select
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function executeResultSet(Select $select)
    {
        $statement = $this->getSql()->prepareStatementForSqlObject($select);
        $rowset    = $statement->execute();

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
        $tableName = $tableName ?: $this->tableName;

        $sql    = $this->getSql()->setTable($tableName);
        $update = $sql->update();

        $rowData = $this->entityToArray($entity, $hydrator);

        //$rowData = array_filter($rowData, 'strlen');// quita todos los nulos y vacios
        $rowData = array_filter($rowData, function($element) {
            return !is_null($element); //retorna solo aquellos que no son null;
        });

        //remove cols
        if (!empty($this->ignoreColsUpdate)) {
            foreach ($this->ignoreColsUpdate as $col) {
                unset($rowData[$col]);
            }
        }

        $update->set($rowData)->where($where);
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
            $entity   = $hydrator->hydrate($entity, $this->getEntityPrototype());
        }

        $getEntityId = $this->entityMethod($this->getId(), 'get');
        $setEntityId = $this->entityMethod($this->getId(), 'set');

        if ($entity->$getEntityId() > 0) {
            $where = new Where;
            $where->equalTo($this->getId(), $entity->$getEntityId());
            $this->update($entity, $where, $this->getTableName());
        } else {
            //var_dump($entity);            exit;
            
            if (empty($entity->$getEntityId())) {
                $entity->$setEntityId(null);
            }
            //var_dump($entity);           exit;
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
