<?php
namespace Neos\Fusion\Form\Runtime\Domain;

use Neos\Utility\Arrays;

class FormState implements \JsonSerializable
{
    protected $formIdentifier = null;
    protected $submittedStepIdentifiers = [];
    protected $currentStepIdentifier = null;
    protected $currentData = null;

    /**
     * FormState constructor.
     * @param array $submittedSteps
     * @param array $currentStep
     * @param null $currentData
     */
    public function __construct(string $formIdentifier, string $currentStepIdentifier, array $currentData, array $submittedStepIdentifiers = [] )
    {
        $this->formIdentifier = $formIdentifier;
        $this->currentStepIdentifier = $currentStepIdentifier;
        $this->currentData = $currentData;
        $this->submittedStepIdentifiers = $submittedStepIdentifiers;
    }

    /**
     * @param string $stepIdentifier
     * @param array $stepData
     */
    public function withDataForStep(string $stepIdentifier, array $stepData): self
    {
        $formIdentifier = $this->getFormIdentifier();
        $currentStep = $this->getCurrentStepIdentifier();
        $currentData = Arrays::arrayMergeRecursiveOverrule($this->getCurrentData(), $stepData);

        $submittedStepIdentifiers = $this->getSubmittedStepIdentifiers();
        if (!in_array($stepIdentifier, $submittedStepIdentifiers)) {
            $submittedStepIdentifiers[] = $stepIdentifier;
        }

        return new static(
            $formIdentifier,
            $currentStep,
            $currentData,
            $submittedStepIdentifiers
        );
    }

    /**
     * @param string $stepIdentifier
     */
    public function withStep(string $stepIdentifier) {
        $formIdentifier = $this->getFormIdentifier();
        $currentData = $this->getCurrentData();
        $submittedStepIdentifiers = $this->getSubmittedStepIdentifiers();

        return new static(
            $formIdentifier,
            $stepIdentifier,
            $currentData,
            $submittedStepIdentifiers
        );
    }

    /**
     * @param string $stepIdentifier
     * @return bool
     */
    public function hasStep(string $stepIdentifier): bool
    {
        return in_array($stepIdentifier, $this->submittedStepIdentifiers);
    }

    /**
     * @return string|null
     */
    public function getFormIdentifier(): ?string
    {
        return $this->formIdentifier;
    }

    /**
     * @return array
     */
    public function getCurrentStepIdentifier(): string
    {
        return $this->currentStepIdentifier;
    }

    /**
     * @return null
     */
    public function getCurrentData()
    {
        return $this->currentData;
    }

    /**
     * @return array
     */
    public function getSubmittedStepIdentifiers(): array
    {
        return $this->submittedStepIdentifiers;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'formIdentifier' => $this->formIdentifier,
            'currentStepIdentifier' => $this->currentStepIdentifier,
            'currentData' => $this->currentData,
            'submittedStepIdentifiers' => $this->submittedStepIdentifiers,
        ];
    }

    /**
     * @param array $data
     * @return FormState
     * @throws \Neos\Flow\ObjectManagement\Exception\UnresolvedDependenciesException
     */
    public static function jsonDeserialize(array $data): self
    {
        return new static(
            $data['formIdentifier'],
            $data['currentStepIdentifier'],
            $data['currentData'],
            $data['submittedStepIdentifiers']
        );
    }
}
