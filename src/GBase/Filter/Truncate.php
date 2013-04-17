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

class Truncate extends AbstractFilter
{

    /**
     * @var array
     */
    protected $options = array(
        'start' => 0,
        'length' => 255,
        'encoding' => 'UTF-8'
    );

    public function __construct(array $options = array())
    {
        if (isset($options['start'])) {
            $options['start'] = empty($options['start']) ? 0 : (int) $options['start'];
        }
        if (isset($options['length']) && empty($options['length'])) {
            $options['length'] = empty($options['length']) ? 255 : (int) $options['length'];
        }

        if (!empty($options)) {
            $this->setOptions($options);
        }
    }

    public function filter($value)
    {
        $value = mb_substr((string) $value, $this->options['start'], $this->options['length'], $this->options['encoding']);
        return $value;
    }

}