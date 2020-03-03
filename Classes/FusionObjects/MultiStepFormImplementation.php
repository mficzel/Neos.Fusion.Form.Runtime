<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Algorithms;
use Neos\Fusion\Form\Domain\Form;
use Neos\Flow\Validation\ValidatorResolver;
use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Fusion\Form\Runtime\Domain\FormState;
use Neos\Fusion\Form\Runtime\Domain\SerializableUploadedFile;
use Neos\Fusion\Form\Runtime\Domain\StepCollectionInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Error\Messages\Result;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Cache\Frontend\VariableFrontend;
use Psr\Http\Message\UploadedFileInterface;

class MultiStepFormImplementation  extends AbstractFusionObject
{
    /**
     * @var PropertyMapper
     * @Flow\Inject
     */
    protected $propertyMapper;

    /**
     * @var PropertyMappingConfiguration
     * @Flow\Inject
     */
    protected $propertyMappingConfiguration;

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
     * @var VariableFrontend
     * @Flow\Inject
     */
    protected $stateCache;

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
    protected function getAction(): ActionInterface
    {
        return  $this->fusionValue('action');
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
        $stateIdentifier = $unvalidatedValues['__state'] ?? null;
        $stepIdentifier = $unvalidatedValues['__step'] ?? null;
        $targetStepIdentifier = $unvalidatedValues['__targetStep'] ?? null;

        // if no values are submitted render the first step directly
        if (!$stepIdentifier) {
            $formState = new FormState (
                $formIdentifier,
                $this->getData()
            );

            return $this->renderForm (
                $formState,
                new Result(),
                [],
                null,
                $stepCollection->getFirstStepIdentifier()
            );
        }

        // restore previous form state
        if ($stateIdentifier) {
            $formState = $this->getCachedFormState($stateIdentifier);
        } else {
            $formState = new FormState (
                $formIdentifier,
                $this->getData()
            );
        }

        // when a step is requested directly no submitted values are handled
        // only already submitted steps can be adressed
        if ($targetStepIdentifier) {
            if ($formState->hasStep($targetStepIdentifier) == false) {
                throw new \Exception("this is fishy");
            }
            return $this->renderForm(
                $formState,
                new Result(),
                [],
                $stateIdentifier,
                $targetStepIdentifier
            );
        }

        $currentStep = $stepCollection->getStepByIdentifier($stepIdentifier);

        // map and validate submittedData
        $submittedData = [];
        $validationResult = new Result();
        $stepValidationConfigurations = $currentStep->getValidationConfigurations();
        $stepTypeConfigurations = $currentStep->getTypeConfigurations();

        if ($stepValidationConfigurations || $stepTypeConfigurations) {
            foreach ($stepValidationConfigurations as $fieldName => $fieldValidationConfigurations) {
                $submittedData[$fieldName] = $unvalidatedValues[$fieldName] ?? null;
                foreach($fieldValidationConfigurations as $pathValidationConfiguration) {
                    $validator = $this->validatorResolver->createValidator(
                        $pathValidationConfiguration['identifier'],
                        $pathValidationConfiguration['options'] ?? []
                    );
                    $validationResult->forProperty($fieldName)->merge($validator->validate($submittedData[$fieldName]));
                }
            }
        }

        // when validation was successfull render next step or invoke the action handlers
        if ($validationResult->hasErrors() === false) {
            $formState = $formState->withDataForStep(
                $stepIdentifier,
                $submittedData
            );

            if ($nextStepIdentifier = $stepCollection->getNextStepIdentifier($stepIdentifier)) {
                $stepIdentifier = $nextStepIdentifier;
            } else {
                //
                // the content of the action response is returned directly
                // everything else is merged into the parent response
                //
                $action = $this->getAction();
                $actionResponse = $action->handle($formState->getCurrentData());
                if ($actionResponse) {
                    $content = $actionResponse->getContent();
                    $actionResponse->setContent('');
                    $actionResponse->mergeIntoParentResponse($this->getRuntime()->getControllerContext()->getResponse());
                    return $content;
                } else {
                    return '';
                }
            }
        }

        // add serialized state to the form data
        $stateIdentifier = $this->storeFormStateToCache($formState, $stateIdentifier);

        return $this->renderForm($formState, $validationResult, $submittedData, $stateIdentifier, $stepIdentifier);
    }

    /**
     * @param string $formIdentifier
     * @return array
     */
    protected function getSubmittedValues(string $formIdentifier): array
    {
        $request = $this->getRuntime()->getControllerContext()->getRequest();
        if ($request->hasArgument($formIdentifier)) {
            $submittedValues = $request->getArgument($formIdentifier);
            if (is_array($submittedValues)) {
                $submittedValues = array_map(
                    function($item) {
                        if ($item instanceof UploadedFileInterface) {
                            return SerializableUploadedFile::fromUploadedFile($item);
                        } else {
                            return $item;
                        }
                    },
                    $submittedValues
                );
            }
            return $submittedValues;
        }
        return [];
    }

    /**
     * @param string $identifier
     * @return FormState
     * @throws \Neos\Flow\Security\Exception\InvalidArgumentForHashGenerationException
     * @throws \Neos\Flow\Security\Exception\InvalidHashException
     */
    protected function getCachedFormState(string $identifier): ?FormState
    {
        $cachedState = $this->stateCache->get($identifier);
        if ($cachedState && $cachedState instanceof FormState) {
            return $cachedState;
        } else {
            return null;
        }
    }

    /**
     * @param FormState $formState
     * @param string|null $identifier
     * @return string
     * @throws \Neos\Cache\Exception
     */
    protected function storeFormStateToCache(FormState $formState, string $identifier = null): string
    {
        if (is_null($identifier)) {
            $identifier = Algorithms::generateUUID();
        }
        $this->stateCache->set($identifier, $formState);
        return $identifier;
    }

    /**
     * @param string|null $identifier
     * @return string
     * @throws \Neos\Cache\Exception
     */
    protected function removeFormStateFromCache(string $identifier = null): void
    {
        $this->stateCache->remove($identifier);
    }

    /**
     * @param string $identifier
     * @param array $data
     * @param string $stepIdentifier
     * @param Result $validationResult
     * @param array $submittedData
     * @param string $stateIdentifier
     * @param string $stepIdentifier
     * @return string
     */
    protected function renderForm(FormState $formState, Result $validationResult, array $submittedData, string $stateIdentifier = null, string $stepIdentifier): string
    {
        $step = $this->getStepCollection()->getStepByIdentifier($stepIdentifier);

        // use a fake request until we can pass the validation result and submitted values directly to the form
        $request = clone $this->getRuntime()->getControllerContext()->getRequest();
        $request->setArgument('__submittedArguments', $submittedData);
        $request->setArgument('__submittedArgumentValidationResults', $validationResult);

        $form = new Form (
            $request,
            $formState->getCurrentData(),
            $formState->getFormIdentifier(),
            null,
            'post',
            'multipart/form-data'
        );

        // push data to context before the content is evaluated
        $this->getRuntime()->pushContextArray([
            'stateIdentifier' => $stateIdentifier,
            'stepIdentifier' => $stepIdentifier,
            'form' => $form,
            'data' => $formState->getCurrentData()
        ]);

        // evaluate content
        $this->getRuntime()->pushContext('content', $step->render());
        $result = $this->fusionValue('renderer');
        $this->getRuntime()->popContext();
        $this->getRuntime()->popContext();
        return $result;
    }
}
