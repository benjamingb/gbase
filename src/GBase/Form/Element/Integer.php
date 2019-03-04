<?php

namespace GBase\Form\Element;

use Zend\Form\Element;
use Zend\InputFilter\InputProviderInterface;
use Zend\I18n\Validator\IsInt;

class Integer extends Element implements InputProviderInterface
{

    /**
     * @var \Zend\Validator\ValidatorInterface
     */
    protected $validator;

    /**
     * Get validator
     *
     * @return \Zend\Validator\ValidatorInterface
     */
    protected function getValidator()
    {
        if (null === $this->validator) {
            $this->validator = new IsInt(array(
                'locale' => 'en_US', // HTML5 uses "100.01" format
            ));
        }
        return $this->validator;
    }

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
                array('name' => 'GBase\Filter\Truncate',
                    'options' => array(
                        'length' => $this->getMaxlength(),
                    )
                ),
                array('name' => 'GBase\Filter\FilterVar',
                    'options' => array(
                        'filter' => 'number_int',
                    )
                )
            ),
            'validators' => array(
                $this->getValidator(),
            ),
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