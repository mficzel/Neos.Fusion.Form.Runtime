<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Process;

use Neos\Error\Messages\Result;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Tests\Features\Bootstrap\SubProcess\SubProcess;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Form\Runtime\Domain\Model\FormStateRepository;
use Neos\Fusion\Form\Runtime\Domain\ProcessInterface;
use Neos\Fusion\Form\Runtime\Domain\SubProcessInterface;
use Neos\Fusion\FusionObjects\DataStructureImplementation;
use Neos\Fusion\Form\Domain\Form;

class MultiStepProcessImplementation extends DataStructureImplementation implements ProcessInterface
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var FormStateRepository
     * @Flow\Inject
     */
    protected $formStateRepository;

    /**
     * @var SubProcessInterface[]
     */
    protected $subProcessses;

    /**
     * @var SubProcessInterface
     */
    protected $currentSubProcess;

    /**
     * @var array
     */
    protected $ignoreProperties = ['identifier'];

    public function evaluate()
    {
        $this->identifier = $this->fusionValue('identifier');
        if (!$this->identifier) {
            $this->identifier = md5($this->path);
        }
        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function handleSubmittedData(array $unvalidatedData = [])
    {
        if (!is_array($unvalidatedData)) {
            return;
        }

        $storeState = $this->formStateRepository->getFormState($this->getIdentifier());

        foreach ($unvalidatedData as $subProcessIdentifier => $unvalidatedSubProcessData) {
            $subProcess = $this->findSubProcessByIdentifier($subProcessIdentifier);
            if ($subProcess) {
                $subProcess->handleSubmittedData($unvalidatedSubProcessData);
                if ($subProcess->isCompleted()) {
                    $storeState[$subProcess->getIdentifier()] = $subProcess->getData();
                    $this->formStateRepository->setFormState($this->getIdentifier(), $storeState);
                } else {
                    $this->currentSubProcess = $subProcess;
                }
            }
        }
    }

    public function isCompleted(): bool
    {
        $subProcesses = $this->getSubProcesses();
        foreach ($subProcesses as $subProcess) {
            if ($subProcess->isRequired() && !$subProcess->isCompleted()) {
                return false;
            }
        }
        return true;
    }

    public function getForm(): ?Form
    {
        if ($subProces = $this->getCurrentProcessStep()) {
            $subProcesForm = $subProces->getForm();
            $subProcesForm->getFieldNamePrefix();

            // use a fake request until we can pass the validation result and submitted values directly to the form
            $request = clone $this->getRuntime()->getControllerContext()->getRequest();
            $data = [$this->getIdentifier() => $subProcesForm->getData()];
            if ($subProcesForm->getResult() && $subProcesForm->getResult()->hasErrors()) {
                $result = (new Result())->forProperty($this->getIdentifier())->merge($subProcesForm->getResult());
                $request->setArgument('__submittedArguments', $data);
                $request->setArgument('__submittedArgumentValidationResults', $result);
            }

            // wrap in form nested in parent namespace
            $form = new Form(
                $subProcesForm->getRequest(),
                $data,
                $this->getIdentifier() . '[' . $subProces->getIdentifier() . ']',
                null,
                'post',
                'multipart/form-data'
            );
            return $form;
        }
        return null;

    }

    public function render():string
    {
        if ($subProces = $this->getCurrentProcessStep()) {
            return $subProces->render();
        }
        return '';
    }

    protected function getCurrentProcessStep(): ?SubProcessInterface
    {
        if ($this->currentSubProcess && $this->currentSubProcess->isAccessible()) {
            return $this->currentSubProcess;
        }
        $nextSubProcesses = $this->findNextSubProcess();
        if ($nextSubProcesses) {
            return $nextSubProcesses;
        }
        return null;
    }


    public function getData(): array
    {
        $result = [];
        $subProcesses = $this->getSubProcesses();
        foreach ($subProcesses as $subProcess) {
            if ($subProcess->isCompleted()) {
                $result = array_merge($result, $subProcess->getData());
            }
        }
        return $result;
    }

    /**
     * @param string $identifier
     * @return SubProcessInterface|null
     */
    protected function findSubProcessByIdentifier(string $identifier): ?SubProcessInterface
    {
        $subProcesses = $this->getSubProcesses();
        return $subProcesses[$identifier] ?? null;
    }

    /**
     * @return SubProcessInterface|null
     */
    protected function findNextSubProcess(): ?SubProcessInterface
    {
        $subProcesses = $this->getSubProcesses();
        foreach ($subProcesses as $subProcess) {
            if ($subProcess->isRequired() && $subProcess->isAccessible() && !$subProcess->isCompleted()) {
                return $subProcess;
            }
        }
        return null;
    }

    /**
     * @return SubProcessInterface[]
     */
    protected function getSubProcesses(): array
    {
        if (is_array($this->subProcessses)) {
            return $this->subProcessses;
        }

        $sortedChildFusionKeys = $this->sortNestedFusionKeys();
        $storedState = $this->formStateRepository->getFormState($this->getIdentifier());

        // instantiate subprocesses
        $this->subProcessses = [];
        foreach ($sortedChildFusionKeys as $childName) {
            $propertyPath = $childName;
            if ($this->isUntypedProperty($this->properties[$childName])) {
                $propertyPath .= '<Neos.Fusion.Form:Process.MultiStepSubProcess>';
            }
            try {
                $subProcess = $this->fusionValue($propertyPath);
            } catch (\Exception $e) {
                $subProcess = $this->runtime->handleRenderingException($this->path . '/' . $childName, $e);
            }
            if ($subProcess === null && $this->runtime->getLastEvaluationStatus() === Runtime::EVALUATION_SKIPPED) {
                continue;
            }

            if ($subProcess instanceof  SubProcessInterface) {
                $namespace = $subProcess->getIdentifier();
                $storedSubState = $storedState[$namespace] ?? null;
                if ($storedSubState) {
                    $subProcess->restoreData($storedSubState);
                }
                $this->subProcessses[$namespace] = $subProcess;
            }
        }

        return $this->subProcessses;
    }

    /**
     * Returns TRUE if the given property has no object type assigned
     *
     * @param mixed $property
     * @return bool
     */
    private function isUntypedProperty($property): bool
    {
        if (!is_array($property)) {
            return false;
        }
        return array_intersect_key(array_flip(Parser::$reservedParseTreeKeys), $property) === [];
    }

}
