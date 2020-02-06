<?php
namespace Neos\Fusion\Form\Runtime\Domain;

use Neos\Error\Messages\Result;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use Neos\Fusion\Form\Runtime\Domain\ActionInterface;

interface StepInterface
{
    public function renderContent():string;

    public function getValidator(): ?ValidatorInterface;
}
