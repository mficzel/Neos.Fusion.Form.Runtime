<?php
namespace Neos\Fusion\Form\Runtime\Domain;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use Neos\Fusion\Form\Domain\Form;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Error\Messages\Result;
use Neos\Flow\Security\Cryptography\HashService;

class SinglePageForm extends Form
{
    /**
     * @var HashService
     * @Flow\Inject
     */
    protected $hashService;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * SinglePageForm constructor.
     * @param ActionRequest $request
     * @param string $identifier
     * @param ValidatorInterface|null $validator
     */
    public function __construct(ActionRequest $request, string $identifier, ValidatorInterface $validator = null)
    {
        // instantiate form
        parent::__construct($request, [], $identifier, '#' . $identifier, 'post');

        // find submitted values from http request
        $allSubmittedValues = $request->getHttpRequest()->getParsedBody();
        if (is_array($allSubmittedValues) && array_key_exists($identifier, $allSubmittedValues)) {
            $this->submittedValues = $allSubmittedValues[$identifier];
        } else {
            $this->submittedValues = null;
        }
        $this->validator = $validator;
    }

    public function initializeObject() {

        $data = [];
        if (is_array($this->submittedValues) && array_key_exists('__trustedProperties', $this->submittedValues)) {
            $trustedPropertiesToken = $this->submittedValues['__trustedProperties'];
            $trustedProperties = unserialize($this->hashService->validateAndStripHmac($trustedPropertiesToken));

            // @todo make this algorithm recursive or call the property mapper
            foreach ($trustedProperties as $trustedPropertyName => $trustedPropertyValue) {
                if (array_key_exists($trustedPropertyName, $this->submittedValues)) {
                    $data[$trustedPropertyName] = $this->submittedValues[$trustedPropertyName];
                }
            }
        }

        if ($this->validator && $data) {
            $this->result = $this->validator->validate($data);
        } else {
            $this->result = new Result();
        }

        if ($this->result->hasErrors() === false) {
            $this->data = $data;
        }
    }
}
