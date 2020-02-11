<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Form\Domain\Form;
use Neos\Flow\Validation\ValidatorResolver;
use Neos\Fusion\Form\Runtime\ActionHandler\ActionHandlerResolver;
use Neos\Fusion\Form\Runtime\Domain\StepCollectionInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Utility\Arrays;
use Neos\Utility\ObjectAccess;
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

    public function evaluate()
    {
        $formIdentifier = $this->getIdentifier();
        $stepCollection = $this->getStepCollection();

        // handle submitted values
        $submittedValues = $this->getSubmittedValues($formIdentifier);

        // if no values are submitted render the first step directly
        if (!array_key_exists('__state' , $submittedValues)) {
            return $this->renderFormStep(
                $formIdentifier,
                $this->getData(),
                $stepCollection->getFirstStepIdentifier(),
                new Result(),
                []
            );
        }

        // restore previous form state
        list($stepIdentifier, $data) = $this->restoreSerializedFormState($submittedValues['__state']);
        $step = $stepCollection->getStepByIdentifier($stepIdentifier);

        // fetch only submitted properties but only those that were trusted
        // @todo make this algorithm recursive or even call the property mapper
        $trustedProperties = unserialize($this->hashService->validateAndStripHmac($submittedValues['__trustedProperties'] ?? ''));
        $submittedData = [];
        foreach ($trustedProperties as $trustedPropertyName => $trustedPropertyValue) {
            if ($trustedPropertyName == '__state') {
                continue;
            }
            if (array_key_exists($trustedPropertyName, $submittedValues)) {
                $submittedData[$trustedPropertyName] = $submittedValues[$trustedPropertyName];
            }
        }

        // validate $submittedData
        $validationResult = new Result();
        if ($validationConfigurations = $step->getValidationConfigurations()) {
            foreach ($validationConfigurations as $path => $pathValidationConfigurations) {
                $pathValue = ObjectAccess::getPropertyPath($submittedData, $path);
                foreach($pathValidationConfigurations as $pathValidationConfiguration) {
                    $pathValidator = $this->validatorResolver->createValidator(
                        $pathValidationConfiguration['identifier'],
                        $pathValidationConfiguration['options'] ?? []
                    );
                    $validationResult->forProperty($path)->merge($pathValidator->validate($pathValue));
                }
            }
        }

        // render next step or call actions
        if ($validationResult->hasErrors() === false) {
            $data = Arrays::arrayMergeRecursiveOverrule($data, $submittedData);
            $stepIdentifier = $stepCollection->getNextStepIdentifier($stepIdentifier);
            if (is_null($stepIdentifier)) {
                return  $this->invokeActionHandlers($data);
            }
        }

        return $this->renderFormStep($formIdentifier, $data, $stepIdentifier, $validationResult, $submittedData);
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
     * @return array
     * @throws \Neos\Flow\Security\Exception\InvalidArgumentForHashGenerationException
     * @throws \Neos\Flow\Security\Exception\InvalidHashException
     */
    protected function restoreSerializedFormState(string $lastState): array
    {
        $state = unserialize($this->hashService->validateAndStripHmac($lastState));
        $stepIdentifier = $state['step'];
        $previousData = $state['data'];
        return array($stepIdentifier, $previousData);
    }

    /**
     * @param string $stepIdentifier
     * @param array $data
     * @return string
     */
    protected function serializeFormState(string $stepIdentifier, array $data): string
    {
        return $this->hashService->appendHmac(
            serialize([
                'step' => $stepIdentifier,
                'data' => $data
            ])
        );
    }

    /**
     * @param string $identifier
     * @param array $data
     * @param string $stepIdentifier
     * @param Result $validationResult
     * @param array $submittedData
     * @return string
     */
    protected function renderFormStep(string $identifier, array $data, string $stepIdentifier, Result $validationResult, array $submittedData): string
    {
        $step = $this->getStepCollection()->getStepByIdentifier($stepIdentifier);

        // add current state to the form data
        $serializedState = $this->serializeFormState($stepIdentifier, $data);
        $data['__state'] = $serializedState;
        $submittedData['__state'] = $serializedState;

        // use a fake request until we can pass the validation result and submitted values directly to the form
        $request = clone $this->getRuntime()->getControllerContext()->getRequest();
        $request->setArgument('__submittedArguments', $submittedData);
        $request->setArgument('__submittedArgumentValidationResults', $validationResult);

        $form = new Form (
            $request,
            $data,
            $identifier,
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
     * @param array $data
     * @return string
     * @throws \Neos\Fusion\Form\Runtime\ActionHandler\NoSuchActionHandlerException
     */
    protected function invokeActionHandlers(array $data): string
    {
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
