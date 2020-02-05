<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Form;

interface StepCollectionInterface
{
    public function hasStepIdentifier(string $identifier): bool;
    public function getStepByIdentifier(string $identifier): StepImplementation;
    public function getFirstStepIdentifier(): ?string;
    public function getNextStepIdentifier(string $previousStepIdentifier): ?string;
}
