<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Form;

use Neos\Fusion\Form\Runtime\Domain\StepInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class StepImplementation extends AbstractFusionObject implements StepInterface
{
    public function evaluate()
    {
        return $this;
    }

    public function render(): string
    {
        return $this->fusionValue('renderer');
    }

    public function getValidationConfigurations(): array
    {
        return $this->fusionValue('validators');
    }

    public function getTypeConfigurations(): array
    {
        return $this->fusionValue('types') ?? [];
    }
}
