<?php
namespace Neos\Fusion\Form\Runtime\Domain\Model;


use Neos\Utility\Arrays;


class FormState
{
    protected $identifier = null;
    protected $formIdentifier = null;
    protected $submittedStepIdentifiers = [];
    protected $currentData = null;

    /**
     * FormState constructor.
     * @param array $submittedSteps
     * @param array $currentStep
     * @param null $currentData
     */
    public function __construct(string $formIdentifier, array $currentData, array $submittedStepIdentifiers = [] )
    {
        $this->formIdentifier = $formIdentifier;
        $this->currentData = $currentData;
        $this->submittedStepIdentifiers = $submittedStepIdentifiers;
    }

    /**
     * @param string $stepIdentifier
     * @param array $stepData
     */
    public function withDataForStep(string $stepIdentifier, array $stepData): self
    {
        $currentData = Arrays::arrayMergeRecursiveOverrule($this->getCurrentData(), $stepData);
        $submittedStepIdentifiers = $this->getSubmittedStepIdentifiers();
        if (!in_array($stepIdentifier, $submittedStepIdentifiers)) {
            $submittedStepIdentifiers[] = $stepIdentifier;
        }

        return new static(
            $this->getFormIdentifier(),
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

}
