<?php
namespace Neos\Fusion\Form\Runtime\Domain;

use Neos\Flow\Validation\Validator\ValidatorInterface;

interface StepInterface
{
    public function render():string;
    public function getValidationConfigurations(): array;
    public function getTypeConfigurations(): array;
}
