<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Form\Domain\Form;

use Neos\Fusion\Form\Runtime\FusionObjects\Form\StepCollectionInterface;
use Neos\Fusion\Form\Runtime\FusionObjects\Form\StepInterface;

use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Utility\Arrays;

class MultiStepFormImplementation  extends AbstractFusionObject
{

    /**
     * @var HashService
     * @Flow\Inject
     */
    protected $hashService;

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

                // fetch new submitted properties
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

                // validata $dataWithSubmittedValues
                $result = $step->validate($submittedData);

                // use next step from now
                if ($result->hasErrors()) {
                    $request = clone $request;
                    $request->setArgument('__submittedArguments', $submittedData);
                    $request->setArgument('__submittedArgumentValidationResults', $result);
                } else {
                    $data = Arrays::arrayMergeRecursiveOverrule($data, $submittedData);
                    $stepIdentifier = $stepCollection->getNextStepIdentifier($stepIdentifier);
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

        //push data to context
        $this->getRuntime()->pushContextArray([
            'form' => $form,
            '__step' => $stepIdentifier,
            '__data' => $this->hashService->appendHmac(serialize($data))
        ]);

        $this->getRuntime()->pushContext('__content', $step->render());
        $result = $this->fusionValue('renderer');
        $this->getRuntime()->popContext();
        $this->getRuntime()->popContext();

        return $result;
    }
}
