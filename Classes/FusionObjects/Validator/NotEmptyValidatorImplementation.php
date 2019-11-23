<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Validator;

use Neos\Flow\Validation\Validator\NotEmptyValidator;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class NotEmptyValidatorImplementation extends AbstractFusionObject
{
    public function evaluate()
    {
        return new NotEmptyValidator();
    }
}
