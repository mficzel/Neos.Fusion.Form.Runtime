<?php

namespace Neos\Fusion\Form\Runtime\Domain;

use Neos\Error\Messages\Result;

interface ProcessingResultInterface
{
    /**
     * @return array
     */
    public function getData(): array;

    /**
     * @return Result
     */
    public function getResult(): Result;

    /**
     * @return bool
     */
    public function hasErrors(): bool;
}
