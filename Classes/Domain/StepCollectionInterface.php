<?php
namespace Neos\Fusion\Form\Runtime\Domain;

interface StepCollectionInterface
{
    public function getStepByIdentifier(string $identifier): StepInterface;
    public function getFirstStepIdentifier(): ?string;
    public function getNextStepIdentifier(string $previousStepIdentifier): ?string;
}
