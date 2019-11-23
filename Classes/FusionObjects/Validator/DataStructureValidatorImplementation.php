<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Validator;

use Neos\Fusion\Form\Runtime\Domain\Validator\DataStructureValidator;
use Neos\Fusion\FusionObjects\DataStructureImplementation;

class DataStructureValidatorImplementation extends DataStructureImplementation
{
    public function evaluate()
    {
        $properties = parent::evaluate();
        $dataStructureValidator = new DataStructureValidator(['properties' => $properties]);
        return $dataStructureValidator;
    }
}
