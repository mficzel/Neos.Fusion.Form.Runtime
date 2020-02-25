<?php
namespace Neos\Fusion\Form\Runtime\Validation\Validator;

use Neos\Flow\Validation\Validator\AbstractValidator;
use Psr\Http\Message\UploadedFileInterface;

/**
 * The given $value is valid if it is an \Neos\Flow\ResourceManagement\PersistentResource of the configured resolution
 * Note: a value of NULL or empty string ('') is considered valid
 */
class UploadedFileValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = array(
        'allowedExtensions' => array([], 'Array of allowed file extensions', 'array', false),
        'allowedMediaTypes' => array([], 'Array of allowed media types', 'array', false)
    );

    /**
     * The given $value is valid if it is an \Psr\Http\Message\UploadedFileInterface of the configured resolution
     * Note: a value of NULL or empty string ('') is considered valid
     *
     * @param UploadedFileInterface $uploadedFile
     * @return void
     * @api
     */
    protected function isValid($uploadedFile)
    {
        if (!$uploadedFile instanceof UploadedFileInterface) {
            $this->addError('The given value was not a UploadedFileInterface instance.', 1582656471);
            return;
        }

        $fileExtension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $fileMediaType = $uploadedFile->getClientMediaType();
        if ($this->options['allowedExtensions'] && !in_array($fileExtension, $this->options['allowedExtensions'])) {
            $this->addError('The file extension "%s" is not allowed.', 1582656472, array($fileExtension));
        }
        if ($this->options['allowedMediaTypes'] && !in_array($fileMediaType, $this->options['allowedMediaTypes'])) {
            $this->addError('The file media type "%s" is not allowed.', 1582656473, array($fileMediaType));
        }
    }
}
