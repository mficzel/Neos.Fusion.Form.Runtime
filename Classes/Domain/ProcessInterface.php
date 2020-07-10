<?php
namespace Neos\Fusion\Form\Runtime\Domain;


use Neos\Fusion\Form\Domain\Form;

interface ProcessInterface
{
    public function getIdentifier(): string;

    public function handleSubmittedData(array $unvalidatedData = []);

    public function isCompleted(): bool;

    public function getForm(): ?Form;

    public function render(): string;

    public function getData(): array;
}
