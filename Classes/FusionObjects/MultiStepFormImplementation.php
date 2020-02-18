<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Form\Domain\Form;
use Neos\Flow\Validation\ValidatorResolver;
use Neos\Fusion\Form\Runtime\Domain\ActionHandlerResolver;
use Neos\Fusion\Form\Runtime\Domain\FormState;
use Neos\Fusion\Form\Runtime\Domain\StepCollectionInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Error\Messages\Result;

class MultiStepFormImplementation  extends AbstractFusionObject
{

    /**
     * @var HashService
     * @Flow\Inject
     */
    protected $hashService;

    /**
     * @var ValidatorResolver
     * @Flow\Inject
     */
    protected $validatorResolver;

    /**
     * @var ActionHandlerResolver
     * @Flow\Inject
     */
    protected $actionHandlerResolver;

    /**
     * @return string
     */
    protected function getIdentifier(): string
    {
        return $this->fusionValue('identifier');
    }

    /**
     * @return mixed
     */
    protected function getData()
    {
        return $this->fusionValue('data');
    }

    /**
     * @return StepCollectionInterface
     */
    protected function getStepCollection(): StepCollectionInterface
    {
        return $this->fusionValue('steps');
    }

    /**
     * @return array
     */
    protected function getActionConfigurations(): array
    {
        return  $this->fusionValue('actions');
    }

    /**
     * @return string
     */
    public function evaluate(): string
    {
        $formIdentifier = $this->getIdentifier();
        $stepCollection = $this->getStepCollection();

        // handle submitted values
        $unvalidatedValues = $this->getSubmittedValues($formIdentifier);

        // if no values are submitted render the first step directly
        if (!array_key_exists('__state' , $unvalidatedValues)) {
            $formState = new FormState (
                $formIdentifier,
                $stepCollection->getFirstStepIdentifier(),
                $this->getData()
            );

            return $this->renderForm (
                $formState,
                new Result(),
                []
            );
        }

        // restore previous form state
        $formState = $this->restoreSerializedFormState($unvalidatedValues['__state']);
        if ($formIdentifier !== $formState->getFormIdentifier()) {
            throw new \Exception('Strange things happen here');
        }

        // when a step is requested directly no submitted values are handled
        // only already submitted steps can be adressed
        if (array_key_exists('__step' , $unvalidatedValues)) {
            if ($formState->hasStep($unvalidatedValues['__step'])) {
                $formState = $formState->withStep($unvalidatedValues['__step']);
            }
            return $this->renderForm(
                $formState,
                new Result(),
                []
            );
        }

        $currentStep = $stepCollection->getStepByIdentifier($formState->getCurrentStepIdentifier());

        // validate $submittedData and transfer to data
        $submittedData = [];
        $validationResult = new Result();
        if ($stepValidationConfigurations = $currentStep->getValidationConfigurations()) {
            foreach ($stepValidationConfigurations as $name => $validationConfigurations) {
                $value = $unvalidatedValues[$name] ?? null;
                $submittedData[$name] = $value;
                foreach($validationConfigurations as $pathValidationConfiguration) {
                    $validator = $this->validatorResolver->createValidator(
                        $pathValidationConfiguration['identifier'],
                        $pathValidationConfiguration['options'] ?? []
                    );
                    $validationResult->forProperty($name)->merge($validator->validate($value));
                }
            }
        }

        // when validation was successfull render next step or invoke the action handlers
        if ($validationResult->hasErrors() === false) {
            $formState = $formState->withDataForStep(
                $formState->getCurrentStepIdentifier(),
                $submittedData
            );
            if ($nextIdentifier = $stepCollection->getNextStepIdentifier($formState->getCurrentStepIdentifier())) {
                $formState = $formState->withStep($nextIdentifier);
            } else {
                return  $this->invokeActionHandlers($formState);
            }
        }

        return $this->renderForm($formState, $validationResult, $submittedData);
    }

    /**
     * @param string $formIdentifier
     * @return array
     */
    protected function getSubmittedValues(string $formIdentifier): array
    {
        $request = $this->getRuntime()->getControllerContext()->getRequest();
        $allSubmittedValues = $request->getHttpRequest()->getParsedBody();
        if (is_array($allSubmittedValues) && array_key_exists($formIdentifier, $allSubmittedValues)) {
            $submittedValues = $allSubmittedValues[$formIdentifier];
        } else {
            $submittedValues = [];
        }
        return $submittedValues;
    }

    /**
     * @param string $lastState
     * @return FormState
     * @throws \Neos\Flow\Security\Exception\InvalidArgumentForHashGenerationException
     * @throws \Neos\Flow\Security\Exception\InvalidHashException
     */
    protected function restoreSerializedFormState(string $lastState): FormState
    {
        return FormState::jsonDeserialize(json_decode($this->hashService->validateAndStripHmac($lastState), true));
    }

    /**
     * @param FormState $formState
     * @return string
     */
    protected function serializeFormState(FormState $formState): string
    {
        return $this->hashService->appendHmac(json_encode($formState->jsonSerialize()));
    }

    /**
     * @param string $identifier
     * @param array $data
     * @param string $stepIdentifier
     * @param Result $validationResult
     * @param array $submittedData
     * @return string
     */
    protected function renderForm(FormState $formState, Result $validationResult, array $submittedData): string
    {
        $step = $this->getStepCollection()->getStepByIdentifier($formState->getCurrentStepIdentifier());

        // add serialized state to the form data
        $serializedState = $this->serializeFormState($formState);

        // push __state to data for rendering
        $data = $formState->getCurrentData();
        $data['__state'] = $serializedState;
        $submittedData['__state'] = $serializedState;

        // use a fake request until we can pass the validation result and submitted values directly to the form
        $request = clone $this->getRuntime()->getControllerContext()->getRequest();
        $request->setArgument('__submittedArguments', $submittedData);
        $request->setArgument('__submittedArgumentValidationResults', $validationResult);

        $form = new Form (
            $request,
            $data,
            $formState->getFormIdentifier(),
            null,
            'post'
        );

        // push data to context before the content is evaluated
        $this->getRuntime()->pushContextArray([
            'form' => $form,
            'data' => $data
        ]);

        // evaluate content
        $this->getRuntime()->pushContext('content', $step->render());
        $result = $this->fusionValue('renderer');
        $this->getRuntime()->popContext();
        $this->getRuntime()->popContext();
        return $result;
    }

    /**
     * @param FormState $formState
     * @return string
     * @throws \Neos\Fusion\Form\Runtime\Domain\NoSuchActionHandlerException
     */
    protected function invokeActionHandlers(FormState $formState): string
    {
        $data = $formState->getCurrentData();
        $messages = [];
        $this->getRuntime()->pushContext('data', $data);
        $actionConfigurations = $this->getActionConfigurations();
        foreach ($actionConfigurations as $actionConfiguration) {
            $action = $this->actionHandlerResolver->createActionHandler($actionConfiguration['identifier']);
            $messages[] = $action->handle($this->getRuntime()->getControllerContext(), $actionConfiguration['options'] ?? []);
        }
        $this->getRuntime()->popContext();
        return implode('', array_filter($messages));
    }
}
