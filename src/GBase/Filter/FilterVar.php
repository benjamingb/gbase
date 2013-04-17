<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace GBase\Filter;

use Zend\Filter\AbstractFilter;

class FilterVar extends AbstractFilter
{

    /**
     * @var array
     */
    protected $options = array(
        'filter' => null,
        'foptions' => null
    );

    /**
     * @var array 
     */
    protected $filter = array(
        'email' => FILTER_SANITIZE_EMAIL,
        'encoded' => FILTER_SANITIZE_ENCODED,
        'magic_quotes' => FILTER_SANITIZE_MAGIC_QUOTES,
        'number_float' => FILTER_SANITIZE_NUMBER_FLOAT,
        'number_int' => FILTER_SANITIZE_NUMBER_INT,
        'special_chars' => FILTER_SANITIZE_SPECIAL_CHARS,
        'full_special_chars' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'string' => FILTER_SANITIZE_STRING,
        'stripped' => FILTER_SANITIZE_STRIPPED,
        'url' => FILTER_SANITIZE_URL,
        'unsafe_raw' => FILTER_UNSAFE_RAW
    );

    public function __construct($options = array())
    {
        $this->setOptions($options);
    }

    public function filter($value)
    {
        $options = array('options' => $this->options['foptions']);
        $filter = $this->filter[$this->options['filter']];

        $value = filter_var($value, $filter, $options);
        return $value;
    }

}