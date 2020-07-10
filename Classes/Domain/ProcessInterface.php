<?php
namespace Neos\Fusion\Form\Runtime\Domain;


use Neos\Fusion\Form\Domain\Form;

interface ProcessInterface
{
    public function setIdentifier(string $identifier);

    public function submitData(array $unvalidatedData = []);

    public function isCompleted(): bool;

    public function getForm(): ?Form;

    public function render(): string;

    public function getIdentifier(): string;

    public function getData(): array;
}
