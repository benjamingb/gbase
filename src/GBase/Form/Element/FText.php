<?php

namespace GBase\Form\Element;

use Zend\Form\Element;
use Zend\InputFilter\InputProviderInterface;
use Zend\Validator\Regex as RegexValidator;

class FText extends Element implements InputProviderInterface
{

    /**
     * Provide default input rules for this element
     *
     * Attaches a phone number validator.
     *
     * @return array
     */
    public function getInputSpecification()
    {
        
        return array(
            'name' => $this->getName(),
            'required' => false,
            'filters' => array(
                array('name' => 'Zend\Filter\StringTrim'),
                array('name' => 'Zend\Filter\StripTags'),
                array('name' => 'Zend\Filter\StripNewlines'),
                array('name' => 'GBase\Filter\Truncate',
                    'options' => array(
                        'length' => $this->getMaxlength(),
                    )
                ),
                array('name' => 'GBase\Filter\FilterVar',
                    'options' => array(
                        'filter' => 'string',
                    //'foptions' => array('flag' => FILTER_FLAG_STRIP_HIGH),
                    )
                )
            )
        );
    }

    public function getMaxlength()
    {
        if ($this->hasAttribute('maxlength')) {
            return (int) $this->getAttribute('maxlength');
        }
        return null;
    }

}

