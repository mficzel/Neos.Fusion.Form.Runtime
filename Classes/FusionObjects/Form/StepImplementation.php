<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Form;

use Neos\Error\Messages\Result;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use Neos\Fusion\Form\Runtime\Domain\StepInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class StepImplementation extends AbstractFusionObject implements StepInterface
{
    public function evaluate()
    {
        return $this;
    }

    public function renderContent(): string
    {
        return $this->fusionValue('content');
    }

    public function getValidator(): ?ValidatorInterface
    {
        return $this->fusionValue('validator');
    }
}
