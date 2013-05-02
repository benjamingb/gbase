<?php

namespace GBase\Db\Tree;

class Node
{

    private $left;
    private $right;
    private $id;
    private $pid;
    private $level;
    private $data;
    public $hasChild = false;
    public $numChild = 0;

    public function __construct($nodeData = array(), $keys)
    {
        if (empty($nodeData)) {
            throw new Exception\InvalidArgumentException('Empty array of node information');
        }
        if (empty($keys)) {
            throw new Exception\InvalidArgumentException('Empty keys array');
        }
        $this->id = $nodeData[$keys['id']];
        $this->pid = $nodeData[$keys['pid']];
        $this->left = $nodeData[$keys['left']];
        $this->right = $nodeData[$keys['right']];
        $this->level = $nodeData[$keys['level']];

        $this->data = $nodeData;
        $a = $this->right - $this->left;
        if ($a > 1) {
            $this->hasChild = true;
            $this->numChild = ($a - 1) / 2;
        }
        return $this;
    }

    public function getData($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        } else {
            return null;
        }
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getLeft()
    {
        return $this->left;
    }

    public function getRight()
    {
        return $this->right;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function getId()
    {
        return $this->id;
    }
    
   

    /**
     * Return true if node have chield
     *
     * @return boolean
     */
    public function isParent()
    {
        if ($this->right - $this->left > 1) {
            return true;
        } else {
            return false;
        }
    }

}