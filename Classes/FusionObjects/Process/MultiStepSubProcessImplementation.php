<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Process;

use Neos\Error\Messages\Result;
use Neos\Fusion\Form\Runtime\Domain\SubProcessInterface;

class MultiStepSubProcessImplementation extends SingleStepProcessImplementation implements SubProcessInterface
{

    public function getLabel(): string
    {
        return $this->fusionValue('label');
    }

    public function isAccessible(): bool
    {
        return $this->fusionValue('accessible');
    }

    public function isRequired(): bool
    {
        return $this->fusionValue('required');
    }

    public function restoreData(array $data) {
        $this->validationResult = new Result();
        $this->data = $data;
    }

}
