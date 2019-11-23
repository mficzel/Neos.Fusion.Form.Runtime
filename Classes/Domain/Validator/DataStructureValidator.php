<?php
namespace Neos\Fusion\Form\Runtime\Domain\Validator;

use Neos\Error\Messages\Result;
use Neos\Error\Messages\Result as ErrorResult;
use Neos\Flow\Validation\Exception\InvalidValidationOptionsException;
use Neos\Flow\Validation\Validator\AbstractValidator;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use Neos\Utility\ObjectAccess;

class DataStructureValidator extends AbstractValidator
{

    protected $acceptsEmptyValues = false;

    /**
     * @var array
     */
    protected $supportedOptions = [
        'properties' => array([], 'key value list of subvalidators', 'array')
    ];

    /**
     * @param mixed $value
     */
    protected function isValid($value)
    {
        if (is_null($value)) {
            return;
        }

        if (is_array($value) || ($value instanceof \ArrayAccess) || is_object($value)) {
            $result = $this->getResult() ?: new Result();
            foreach ($this->options['properties'] as $propertyName => $propertyValidator) {
                if (is_array($value) || ($value instanceof \ArrayAccess)) {
                    if (array_key_exists($propertyName, $value)) {
                        $subValue = $value[$propertyName];
                    } else {
                        $subValue = null;
                    }
                } elseif (ObjectAccess::isPropertyGettable($value, $propertyName)) {
                    $subValue = ObjectAccess::getPropertyPath($value, $propertyName);
                } else {
                    $subValue = null;
                }

                if ($propertyValidator instanceof ValidatorInterface) {
                    $subResult = $propertyValidator->validate($subValue);
                    if ($subResult->hasErrors()) {
                        $result->forProperty($propertyName)->merge($subResult);
                    }
                }
            }
        } else {
            $this->addError('DataStructure is expected to be an array or implement ArrayAccess', 1515070099);
        }
    }
}
