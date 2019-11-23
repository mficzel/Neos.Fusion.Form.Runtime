<?php
namespace Neos\Fusion\Form\Runtime\Domain;

use Neos\Flow\Annotations as Flow;
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
     * SinglePageForm constructor.
     * @param ActionRequest $request
     * @param string $identifier
     */
    public function __construct(ActionRequest $request, string $identifier)
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
    }

    public function initializeObject() {
        if (is_array($this->submittedValues) && array_key_exists('__trustedProperties', $this->submittedValues)) {
            $trustedPropertiesToken = $this->submittedValues['__trustedProperties'];
            $trustedProperties = unserialize($this->hashService->validateAndStripHmac($trustedPropertiesToken));
            $data = [];

            // @todo make this algorithm recursive or call the property mapper
            foreach ($trustedProperties as $trustedPropertyName => $trustedPropertyValue) {
                if (array_key_exists($trustedPropertyName, $this->submittedValues)) {
                    $data[$trustedPropertyName] = $this->submittedValues[$trustedPropertyName];
                }
            }
            $this->data = $data;
        }

        // @todo apply validations to the form data

        // @todo put actual validation results here
        $this->result = new Result();
    }

}
