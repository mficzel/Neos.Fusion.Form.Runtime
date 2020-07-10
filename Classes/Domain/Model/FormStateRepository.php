<?php
namespace Neos\Fusion\Form\Runtime\Domain\Model;

use Neos\Flow\Annotations as Flow;

/**
 * Class FormStateRepository
 * @FLow\Scope("session")
 */
class FormStateRepository
{
    /**
     * @var FormState[]
     */
    protected $formStates = [];

    public function setFormState(string $identifier, array $state) {
        $this->formStates[$identifier] = $state;
    }

    public function hasFormState(string $identifier): bool
    {
        return array_key_exists($identifier, $this->formStates);
    }

    public function getFormState(string $identifier): array
    {
        return $this->formStates[$identifier] ?? [];
    }

    public function unsetFormState(string $identifier): void
    {
        unset($this->formStates[$identifier]);
    }

}
