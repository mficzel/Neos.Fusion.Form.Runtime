<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Process;

use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Result;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Validation\ValidatorResolver;
use Neos\Fusion\Form\Domain\Form;
use Neos\Fusion\Form\Runtime\Domain\ProcessInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class SingleStepProcessImplementation extends AbstractFusionObject implements ProcessInterface
{


    /**
     * @var ValidatorResolver
     * @Flow\Inject
     */
    protected $validatorResolver;

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
     * @var string
     */
    protected $identifier;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $submittedData = [];

    /**
     * @var Result
     */
    protected $validationResult;

    public function evaluate()
    {
        return $this;
    }

    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function submitData(array $unvalidatedData = [])
    {
        $model = $this->fusionValue('model');

        $processedData = [];
        $processValidationResult = new Result();

        if ($model) {
            foreach ($model as $propertyName => $propertyModel) {
                $propertyValue = $unvalidatedData[$propertyName] ?? null;
                $propertyValidationResult = new Result();

                //
                // property mapping
                //
                if (array_key_exists('type', $propertyModel)) {
                    $mappedValue = $this->propertyMapper->convert($propertyValue, $propertyModel['type'], $this->propertyMappingConfiguration);
                    $mappingResult = $this->propertyMapper->getMessages();
                    if ($mappingResult->hasErrors()) {
                        $propertyValidationResult->merge($mappingResult);
                    } else {
                        $propertyValue = $mappedValue;
                    }
                }

                //
                // validation
                //
                if (!$propertyValidationResult->hasErrors() && array_key_exists('validators', $propertyModel)) {
                    foreach ($propertyModel['validators'] as $pathValidationConfiguration) {
                        $validator = $this->validatorResolver->createValidator(
                            $pathValidationConfiguration['identifier'],
                            $pathValidationConfiguration['options'] ?? []
                        );
                        $propertyValidationResult->merge($validator->validate($propertyValue));
                    }
                }

                if (!$propertyValidationResult->hasErrors()) {
                    $processedData[$propertyName] = $propertyValue;
                }

                $processValidationResult->forProperty($propertyName)->merge($propertyValidationResult);
            }

            $this->validationResult = $processValidationResult;
            $this->submittedData = $unvalidatedData;
            if ($processValidationResult->hasErrors()  == false) {
                $this->data = $processedData;
            }
        } else {
            $this->submittedData = $unvalidatedData;
            $this->validationResult = new Result();
            $this->data = [];
        }
    }

    public function isCompleted(): bool
    {
        return ($this->validationResult && !$this->validationResult->hasErrors());
    }

    public function getForm(): ?Form
    {
        // use a fake request until we can pass the validation result and submitted values directly to the form
        $request = clone $this->getRuntime()->getControllerContext()->getRequest();
        if ($this->validationResult && $this->validationResult->hasErrors()) {
            $request->setArgument('__submittedArguments', $this->submittedData);
            $request->setArgument('__submittedArgumentValidationResults', $this->validationResult);
        }

        $form = new Form (
            $request,
            $this->getData(),
            $this->identifier,
            null,
            'post',
            'multipart/form-data'
        );

        return $form;
    }

    public function render(): string
    {
        return $this->fusionValue('renderer');
    }

    public function getData(): array
    {
        return $this->data;
    }
}
