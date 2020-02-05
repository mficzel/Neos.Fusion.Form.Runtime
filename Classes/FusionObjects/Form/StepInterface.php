<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Form;

use Neos\Error\Messages\Result;

interface StepInterface
{
    public function render():string;

    public function validate($data): Result;
}
