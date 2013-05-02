<?php

namespace GBase\Db\Tree;

use GBase\Db\Tree\Node;

/**
 * TODO implements iterators
 *
 */
class NodeSet implements \Iterator
{

    private $nodes = array();
    private $currentNode = 0;
    private $current = 0;

    function __construct()
    {
        $this->nodes = array();
        $this->current = 0;
        $this->currentNode = 0;
        $this->count = 0;
    }

    function addNode(Node $node)
    {
        $this->nodes[$this->currentNode] = $node;
        $this->count++;
        return++$this->currentNode;
    }

    function count()
    {
        return $this->count;
    }

    function valid()
    {
        return isset($this->nodes[$this->current]);
    }

    function next()
    {
        if ($this->current > $this->currentNode) {
            return false;
        } else {
            return $this->current++;
        }
    }

    function key()
    {
        return $this->current;
    }

    function current()
    {
        return $this->nodes[$this->current];
    }

    function rewind()
    {
        $this->current = 0;
    }

}