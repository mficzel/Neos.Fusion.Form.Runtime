<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects;

use GuzzleHttp\Psr7\Uri;
use Neos\Error\Messages\Result;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Form\Domain\Form;

use Neos\Flow\Validation\ValidatorResolver;

use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Fusion\Form\Runtime\Domain\StepCollectionInterface;
use Neos\Fusion\Form\Runtime\Domain\StepInterface;

use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Utility\Arrays;
use Neos\Utility\ObjectAccess;

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
     * @return ActionInterface|null
     */
    protected function getAction(): ?ActionInterface
    {
        return  $this->fusionValue('action');
    }

    public function evaluate()
    {
        $request = $this->getRuntime()->getControllerContext()->getRequest();
        $identifier = $this->getIdentifier();

        // get submitted values from http request
        $allSubmittedValues = $request->getHttpRequest()->getParsedBody();
        if (is_array($allSubmittedValues) && array_key_exists($identifier, $allSubmittedValues)) {
            $submittedValues = $allSubmittedValues[$identifier];
        } else {
            $submittedValues = [];
        }

        $stepCollection = $this->getStepCollection();

        // handle submitted values
        $data = $this->getData();
        $stepIdentifier = $submittedValues['__step'] ?? null;
        if ($stepIdentifier) {

            if (!$stepCollection->hasStepIdentifier($stepIdentifier)) {
                throw \Exception("i do not like this");
            }

            $step = $stepCollection->getStepByIdentifier($stepIdentifier);

            if (is_array($submittedValues) && array_key_exists('__trustedProperties', $submittedValues)) {
                $trustedPropertiesToken = $submittedValues['__trustedProperties'];
                $trustedProperties = unserialize($this->hashService->validateAndStripHmac($trustedPropertiesToken));

                // restore data from previous step
                if (array_key_exists( '__data', $submittedValues)) {
                    $previousData = unserialize($this->hashService->validateAndStripHmac($submittedValues['__data']));
                    if ($previousData) {
                        $data = Arrays::arrayMergeRecursiveOverrule($data, $previousData);
                    }
                }

                // fetch newly submitted properties but only those that were trusted
                // @todo make this algorithm recursive or call the property mapper
                $submittedData = [];
                foreach ($trustedProperties as $trustedPropertyName => $trustedPropertyValue) {
                    if (in_array($trustedPropertyName, ['__data', '__step'])) {
                        continue;
                    }

                    if (array_key_exists($trustedPropertyName, $submittedValues)) {
                        $submittedData[$trustedPropertyName] = $submittedValues[$trustedPropertyName];
                    }
                }

                // validata $submittedData
                $result = new Result();
                if ($validationConfigurations = $step->getValidationConfiguration()) {
                    foreach ($validationConfigurations as $path => $pathValidationConfigurations) {
                        $pathValue = ObjectAccess::getPropertyPath($submittedData, $path);
                        foreach($pathValidationConfigurations as $pathValidationConfiguration) {
                            $pathValidator = $this->validatorResolver->createValidator($pathValidationConfiguration['class'], $pathValidationConfiguration['options'] ?? []);
                            $result->forProperty($path)->merge($pathValidator->validate($pathValue));
                        }
                    }
                }

                // rerender current step on error
                if ($result->hasErrors()) {
                    $request = clone $request;
                    $request->setArgument('__submittedArguments', $submittedData);
                    $request->setArgument('__submittedArgumentValidationResults', $result);
                } else {
                    $data = Arrays::arrayMergeRecursiveOverrule($data, $submittedData);
                    $stepIdentifier = $stepCollection->getNextStepIdentifier($stepIdentifier);

                    if (is_null($stepIdentifier) && $action = $this->getAction()) {
                        $this->getRuntime()->pushContext('data', $data);
                        $message = $action->execute($data);
                        $this->getRuntime()->popContext();
                        return $message;
                    }

                    $step = $stepCollection->getStepByIdentifier($stepIdentifier);
                }
            }
        } else {
            $stepIdentifier = $stepCollection->getFirstStepIdentifier();
            $step = $stepCollection->getStepByIdentifier($stepIdentifier);
        }

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
            '__step' => $stepIdentifier,
            '__data' => $this->hashService->appendHmac(serialize($data)),
            'data' => $data
        ]);

        // evaluate content
        $this->getRuntime()->pushContext('__content', $step->render());
        $result = $this->fusionValue('renderer');
        $this->getRuntime()->popContext();
        $this->getRuntime()->popContext();

        return $result;
    }
}
