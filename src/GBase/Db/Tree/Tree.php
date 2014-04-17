<?php

namespace GBase\Db\Tree;

use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Expression;
use GBase\Db\Tree\Node;
use GBase\Db\Tree\NodeSet;

class Tree extends AbstractTableGateway
{

    private $id;
    private $left;
    private $right;
    private $level;
    private $pid;

    /**
     * Array of additional tables
     *
     * array(
     *  [$tableName] => array(
     *              ['joinCondition']
     *              ['fields']
     *          )
     * )
     *
     * @var array
     */
    private $extTables = array();

    function __construct(Adapter $adapter, $table)
    {
        $this->adapter = $adapter;
        $this->setTable($table);
        $this->initialize();
    }

    /**
     * set name of id field
     *
     * @param string $name
     * @return \GBase\Db\Tree\Tree
     */
    public function setIdField($name)
    {
        $this->id = $name;
        return $this;
    }

    /**
     * set name of left field
     *
     * @param string $name
     * @return \GBase\Db\Tree\Tree
     */
    public function setLeftField($name)
    {
        $this->left = $name;
        return $this;
    }

    /**
     * set name of right field
     *
     * @param string $name
     * @return \GBase\Db\Tree\Tree
     */
    public function setRightField($name)
    {
        $this->right = $name;
        return $this;
    }

    /**
     * set name of level field
     *
     * @param string $name
     * @return \GBase\Db\Tree\Tree
     */
    public function setLevelField($name)
    {
        $this->level = $name;
        return $this;
    }

    /**
     * set name of pid Field
     *
     * @param string $name
     * @return \GBase\Db\Tree\Tree
     */
    public function setPidField($name)
    {
        $this->pid = $name;
        return $this;
    }

    /**
     * set table name
     * 
     * @param string $name
     * @return \GBase\Db\Tree\Tree
     */
    public function setTable($name)
    {
        $this->table = $name;
        return $this;
    }

    public function getKeys()
    {
        $keys          = array();
        $keys['id']    = $this->id;
        $keys['left']  = $this->left;
        $keys['right'] = $this->right;
        $keys['pid']   = $this->pid;
        $keys['level'] = $this->level;
        return $keys;
    }

    /**
     * Cleare table and add root element
     *
     */
    public function clear($data = array())
    {
        /* $sql = 'SET FOREIGN_KEY_CHECKS=0;';
          $sql .= 'TRUNCATE ' . $this->table;
          $sql .= 'SET FOREIGN_KEY_CHECKS=1;'; */

        // clearing table
        $this->adapter->query('SET FOREIGN_KEY_CHECKS=0')->execute();
        $this->adapter->query('TRUNCATE ' . $this->table)->execute();
        $this->adapter->query('SET FOREIGN_KEY_CHECKS=1')->execute();

        // prepare data for root element
        $data[$this->pid]   = 0;
        $data[$this->left]  = 1;
        $data[$this->right] = 2;
        $data[$this->level] = 0;


        try {
            $this->insert($data);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        return $this->getLastInsertValue();
    }

    public function getNodeInfo($ID)
    {
        $rowset = $this->select(array($this->id => $ID));
        $row    = $rowset->current();
        if (!$row) {
            return array();
        }
        return $row;
    }

    /**
     * insert child 
     * 
     * @param string $id  //parent_id
     * @param string $data 
     */
    public function appendChild($id, $data)
    {

        if (!$info = $this->getNodeInfo($id)) {
            return false;
        }

        $data[$this->left]  = $info[$this->right];
        $data[$this->right] = $info[$this->right] + 1;
        $data[$this->level] = $info[$this->level] + 1;
        $data[$this->pid]   = $id;


        // creating a place for the record being inserted
        if ($id) {
            $this->getAdapter()->getDriver()->getConnection()->beginTransaction();

            try {
                $update = new Update();
                $update->table($this->table);
                $update->set(array(
                    $this->left  => new Expression("IF({$this->left} > {$info[$this->left]}, {$this->left}+2, {$this->left})"),
                    $this->right => new Expression("IF({$this->right} >= {$info[$this->right]}, {$this->right}+2, {$this->right})")
                ));
                $update->where("{$this->right} >= {$info[$this->right]}");

                $sql       = new Sql($this->adapter);
                $statement = $sql->prepareStatementForSqlObject($update);
                $statement->execute();

                $this->insert($data);
                $this->getAdapter()->getDriver()->getConnection()->commit();
            } catch (PDOException $p) {
                $this->getAdapter()->getDriver()->getConnection()->rollBack();
                echo $p->getMessage();
                exit();
            } catch (Exception $e) {
                $this->getAdapter()->getDriver()->getConnection()->rollBack();
                echo $e->getMessage();
                echo $sql;
                var_dump($data);
                exit();
            }
            // TODO: change to ZEND LIBRARY
            $res = $this->getLastInsertValue();
            return $res;
            //return$this->adapter->fetchOne('select last_insertid()');
            //return$this->adapter->lastInsertId();
        }
        return false;
    }

    public function checkNodes()
    {

        $qi = function($name) {
            return $this->adapter->platform->quoteIdentifier($name);
        };

        $select = "SELECT t1.*, COUNT(t1.{$this->id}) AS rep, MAX(t3.{$this->right}) AS maxright ";
        $select .=" FROM {$qi($this->table)} as t1, {$qi($this->table)} as t2, {$qi($this->table)} as t3 ";
        $select .=" WHERE t1.{$this->left} <> t2.{$this->left}";
        $select .=" AND t1.{$this->left} <> t2.{$this->left}";
        $select .=" AND t1.{$this->left} <> t2.{$this->right}";
        $select .=" AND t1.{$this->right} <> t2.{$this->right}";
        $select .=" GROUP BY t1.{$this->id}";
        $select .=" HAVING maxright <> SQRT(4 * rep + 1) + 1";

        $statement = $this->adapter->query($select);
        $results   = $statement->execute();
        return $results->current();
    }

    public function insertBefore($ID, $data)
    {
        
    }

    public function removeNode($ID)
    {
        //falta adaptaer e implementr con Zf2
        return false;
        if (!$info = $this->getNodeInfo($ID)) {
            return false;
        }

        if ($ID) {
            $this->adapter->beginTransaction();
            try {
                // DELETE FROM my_tree WHERE left_key >= $left_key AND right_key <= $right_key
                $this->adapter->delete($this->table, $this->left . ' >= ' . $info[$this->left] . ' AND ' . $this->right . ' <= ' . $info[$this->right]);

                // UPDATE my_tree SET left_key = IF(left_key > $left_key, left_key – ($right_key - $left_key + 1), left_key), right_key = right_key – ($right_key - $left_key + 1) WHERE right_key > $right_key
                $sql = 'UPDATE ' . $this->table . '
					SET
						' . $this->left . ' = IF(' . $this->left . ' > ' . $info[$this->left] . ', ' . $this->left . ' - ' . ($info[$this->right] - $info[$this->left] + 1) . ', ' . $this->left . '),
						' . $this->right . ' = ' . $this->right . ' - ' . ($info[$this->right] - $info[$this->left] + 1) . '
					WHERE
						' . $this->right . ' > ' . $info[$this->right];
                $this->adapter->query($sql);
                $this->adapter->commit();
                return new Varien_Db_Tree_Node($info, $this->getKeys());
                ;
            } catch (Exception $e) {
                $this->adapter->rollBack();
                echo $e->getMessage();
            }
        }
    }

    public function moveNode($eId, $pId, $aId = 0)
    {

        //falta adaptaer e implementr con Zf2
        return false;
        $eInfo = $this->getNodeInfo($eId);
        $pInfo = $this->getNodeInfo($pId);


        $leftId  = $eInfo[$this->left];
        $rightId = $eInfo[$this->right];
        $level   = $eInfo[$this->level];

        $leftIdP  = $pInfo[$this->left];
        $rightIdP = $pInfo[$this->right];
        $levelP   = $pInfo[$this->level];

        if ($eId == $pId || $leftId == $leftIdP || ($leftIdP >= $leftId && $leftIdP <= $rightId) || ($level == $levelP + 1 && $leftId > $leftIdP && $rightId < $rightIdP)) {
            //echo "alert('cant_move_tree');";
            return FALSE;
        }

        if ($leftIdP < $leftId && $rightIdP > $rightId && $levelP < $level - 1) {
            $sql = 'UPDATE ' . $this->table . ' SET '
                    . $this->level . ' = CASE WHEN ' . $this->left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->level . sprintf('%+d', -($level - 1) + $levelP) . ' ELSE ' . $this->level . ' END, '
                    . $this->right . ' = CASE WHEN ' . $this->right . ' BETWEEN ' . ($rightId + 1) . ' AND ' . ($rightIdP - 1) . ' THEN ' . $this->right . '-' . ($rightId - $leftId + 1) . ' '
                    . 'WHEN ' . $this->left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->right . '+' . ((($rightIdP - $rightId - $level + $levelP) / 2) * 2 + $level - $levelP - 1) . ' ELSE ' . $this->right . ' END, '
                    . $this->left . ' = CASE WHEN ' . $this->left . ' BETWEEN ' . ($rightId + 1) . ' AND ' . ($rightIdP - 1) . ' THEN ' . $this->left . '-' . ($rightId - $leftId + 1) . ' '
                    . 'WHEN ' . $this->left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->left . '+' . ((($rightIdP - $rightId - $level + $levelP) / 2) * 2 + $level - $levelP - 1) . ' ELSE ' . $this->left . ' END '
                    . 'WHERE ' . $this->left . ' BETWEEN ' . ($leftIdP + 1) . ' AND ' . ($rightIdP - 1);
        } elseif ($leftIdP < $leftId) {
            $sql = 'UPDATE ' . $this->table . ' SET '
                    . $this->level . ' = CASE WHEN ' . $this->left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->level . sprintf('%+d', -($level - 1) + $levelP) . ' ELSE ' . $this->level . ' END, '
                    . $this->left . ' = CASE WHEN ' . $this->left . ' BETWEEN ' . $rightIdP . ' AND ' . ($leftId - 1) . ' THEN ' . $this->left . '+' . ($rightId - $leftId + 1) . ' '
                    . 'WHEN ' . $this->left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->left . '-' . ($leftId - $rightIdP) . ' ELSE ' . $this->left . ' END, '
                    . $this->right . ' = CASE WHEN ' . $this->right . ' BETWEEN ' . $rightIdP . ' AND ' . $leftId . ' THEN ' . $this->right . '+' . ($rightId - $leftId + 1) . ' '
                    . 'WHEN ' . $this->right . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->right . '-' . ($leftId - $rightIdP) . ' ELSE ' . $this->right . ' END '
                    . 'WHERE (' . $this->left . ' BETWEEN ' . $leftIdP . ' AND ' . $rightId . ' '
                    . 'OR ' . $this->right . ' BETWEEN ' . $leftIdP . ' AND ' . $rightId . ')';
        } else {
            $sql = 'UPDATE ' . $this->table . ' SET '
                    . $this->level . ' = CASE WHEN ' . $this->left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->level . sprintf('%+d', -($level - 1) + $levelP) . ' ELSE ' . $this->level . ' END, '
                    . $this->left . ' = CASE WHEN ' . $this->left . ' BETWEEN ' . $rightId . ' AND ' . $rightIdP . ' THEN ' . $this->left . '-' . ($rightId - $leftId + 1) . ' '
                    . 'WHEN ' . $this->left . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->left . '+' . ($rightIdP - 1 - $rightId) . ' ELSE ' . $this->left . ' END, '
                    . $this->right . ' = CASE WHEN ' . $this->right . ' BETWEEN ' . ($rightId + 1) . ' AND ' . ($rightIdP - 1) . ' THEN ' . $this->right . '-' . ($rightId - $leftId + 1) . ' '
                    . 'WHEN ' . $this->right . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->right . '+' . ($rightIdP - 1 - $rightId) . ' ELSE ' . $this->right . ' END '
                    . 'WHERE (' . $this->left . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ' '
                    . 'OR ' . $this->right . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ')';
        }

        $db = $this->getAdapter()->getDriver()->getConnection();
        $db->beginTransaction();
        try {
            $statement = $this->adapter->query($sql);
            $statement->execute();
            $db->commit();
            //echo "alert('node moved');";
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            //echo "alert('node not moved: fatal error');";
            echo $e->getMessage();
            echo "<br>\r\n";
            echo $sql;
            echo "<br>\r\n";
            exit();
        }
    }

    public function __moveNode($eId, $pId, $aId = 0)
    {

        //falta adaptaer e implementr con Zf2
        return false;
        $eInfo = $this->getNodeInfo($eId);
        if ($pId != 0) {
            $pInfo = $this->getNodeInfo($pId);
        }
        if ($aId != 0) {
            $aInfo = $this->getNodeInfo($aId);
        }

        $level     = $eInfo[$this->level];
        $left_key  = $eInfo[$this->left];
        $right_key = $eInfo[$this->right];
        if ($pId == 0) {
            $level_up = 0;
        } else {
            $level_up = $pInfo[$this->level];
        }

        $right_key_near = 0;
        $left_key_near  = 0;

        if ($pId == 0) { //move to root
            $right_key_near = $this->adapter->fetchOne('SELECT MAX(' . $this->right . ') FROM ' . $this->table);
        } elseif ($aId != 0 && $pID == $eInfo[$this->pid]) { // if we have after ID
            $right_key_near = $aInfo[$this->right];
            $left_key_near  = $aInfo[$this->left];
        } elseif ($aId == 0 && $pId == $eInfo[$this->pid]) { // if we do not have after ID
            $right_key_near = $pInfo[$this->left];
        } elseif ($pId != $eInfo[$this->pid]) {
            $right_key_near = $pInfo[$this->right] - 1;
        }


        $skewlevel = $pInfo[$this->level] - $eInfo[$this->level] + 1;
        $skew_tree = $eInfo[$this->right] - $eInfo[$this->left] + 1;

        echo "alert('" . $right_key_near . "');";

        if ($right_key_near > $right_key) { // up
            echo "alert('move up');";
            $skew_edit = $right_key_near - $left_key + 1;
            $sql       = 'UPDATE ' . $this->table . '
                SET
                ' . $this->right . ' = IF(' . $this->left . ' >= ' . $eInfo[$this->left] . ', ' . $this->right . ' + ' . $skew_edit . ', IF(' . $this->right . ' < ' . $eInfo[$this->left] . ', ' . $this->right . ' + ' . $skew_tree . ', ' . $this->right . ')),
                ' . $this->level . ' = IF(' . $this->left . ' >= ' . $eInfo[$this->left] . ', ' . $this->level . ' + ' . $skewlevel . ', ' . $this->level . '),
                ' . $this->left . ' = IF(' . $this->left . ' >= ' . $eInfo[$this->left] . ', ' . $this->left . ' + ' . $skew_edit . ', IF(' . $this->left . ' > ' . $right_key_near . ', ' . $this->left . ' + ' . $skew_tree . ', ' . $this->left . '))
                WHERE ' . $this->right . ' > ' . $right_key_near . ' AND ' . $this->left . ' < ' . $eInfo[$this->right];
        } elseif ($right_key_near < $right_key) { // down
            echo "alert('move down');";
            $skew_edit = $right_key_near - $left_key + 1 - $skew_tree;
            $sql       = 'UPDATE ' . $this->table . '
                SET
                    ' . $this->left . ' = IF(' . $this->right . ' <= ' . $right_key . ', ' . $this->left . ' + ' . $skew_edit . ', IF(' . $this->left . ' > ' . $right_key . ', ' . $this->left . ' - ' . $skew_tree . ', ' . $this->left . ')),
                    ' . $this->level . ' = IF(' . $this->right . ' <= ' . $right_key . ', ' . $this->level . ' + ' . $skewlevel . ', ' . $this->level . '),
                    ' . $this->right . ' = IF(' . $this->right . ' <= ' . $right_key . ', ' . $this->right . ' + ' . $skew_edit . ', IF(' . $this->right . ' <= ' . $right_key_near . ', ' . $this->right . ' - ' . $skew_tree . ', ' . $this->right . '))
                WHERE
                    ' . $this->right . ' > ' . $left_key . ' AND ' . $this->left . ' <= ' . $right_key_near;
        }


        $this->adapter->beginTransaction();
        try {
            $this->adapter->query($sql);
            //$afrows =$this->adapter->get
            $this->adapter->commit();
        } catch (Exception $e) {
            $this->adapter->rollBack();
            echo $e->getMessage();
            echo "<br>\r\n";
            echo $sql;
            echo "<br>\r\n";
            exit();
        }
        echo "alert('node added')";
    }

    public function addTable($tableName, $joinCondition, $fields = '*')
    {
        //falta adaptaer e implementr con Zf2
        return false;
        $this->extTables[$tableName] = array(
            'joinCondition' => $joinCondition,
            'fields'        => $fields
        );
    }

    protected function _addExtTablesToSelect(Zend_Db_Select &$select)
    {
        //falta adaptaer e implementr con Zf2
        return false;
        foreach ($this->extTables as $tableName => $info) {
            $select->joinInner($tableName, $info['joinCondition'], $info['fields']);
        }
    }

    public function getParent($ID, $maxLevel = null)
    {
        try {
            $info = $this->getNodeInfo($ID);
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }

        $level = null;
        if (empty($maxLevel) || $info[$this->level] <= $maxLevel) {
            $level = $info[$this->level] - 1;
        }

        $keys = $this->getKeys();
        $data = $this->select(function($select) use ($info, $level, $keys) {
            $select->where("{$keys['left']} <= {$info[$keys['left']]}");
            $select->where("{$keys['right']} >= {$info[$keys['right']]}");
            if (null !== $level) {
                $select->where("{$keys['level']} = {$level}");
            }
            $select->order($keys['left']);
        });

        $nodeSet = new NodeSet;
        foreach ($data as $node) {
            $nodeSet->addNode(new Node($node, $this->getKeys()));
        }
        return $nodeSet;
    }

    public function getChildren($ID, $startlevel = 0, $endlevel = 0)
    {
        try {
            $info = $this->getNodeInfo($ID);
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }

        $minLevel = null;
        if (!empty($startlevel) && empty($endlevel)) {
            $minLevel = $info[$this->level] + $startlevel;
        }

        $keys = $this->getKeys();
        $data = $this->select(function($select) use ($info, $minLevel, $keys) {
            $select->where("{$keys['left']} >= {$info[$keys['left']]}");
            $select->where("{$keys['right']} <= {$info[$keys['right']]}");
            if (null !== $minLevel) {
                $select->where("{$keys['level']} = {$minLevel}");
            }
            $select->order($keys['left']);
        });



        $nodeSet = new NodeSet;
        foreach ($data as $node) {
            $nodeSet->addNode(new Node($node, $this->getKeys()));
        }
        return $nodeSet;
    }

    public function getNode($nodeId)
    {
        $data = $this->select(array($this->id => $nodeId));
        $row  = $data->current();
        if (!$row) {
            return false;
        }
        return new Node($row, $this->getKeys());
    }

}

/**** example use ***

  $tree = new Tree($this->getDbAdapter(), $this->tableName);
  $tree->setIdField($this->id)
  ->setLeftField('lft')
  ->setRightField('rgt')
  ->setLevelField('level')
  ->setPidField('parent_id')
  ;
 //limpiando la data y reiniciando el arbol
  $tree->clear(array('codigo' => 0, 'linea' => 'root'));
 
  //informcion del 
  $nodeInfo = $tree->getNodeInfo(1);

  //insertando hijo
  $tree->appendChild(1, array('codigo' => 71, 'linea' => 'ÉTica'));
  $tree->appendChild(2, array('codigo' => 4, 'linea' => 'xÉTica'));
  $tree->appendChild(3, array('codigo' => 44, 'linea' => 'xxÉTica'));

  //buscando hijos (se puede saber el siguiente nodo y el anterior)
  $children = $tree->getChildren(1, 1, 4);

  //obteniendo nodo en objeto (isParent()  determina su condicion hijo, padre)
  $nodoObject = $tree->getNode(2);

 //retorna un arrgelo si el arbol esta corrupto caso contrario devuelve falso 
$result =  $tree->checkNodes();

  ********/ 