<?php
namespace Neos\Fusion\Form\Runtime\Domain;

use Neos\Flow\Http\Headers;
use Neos\Flow\Mvc\Controller\ControllerContext;

class ActionResponse implements ActionResponseInterface
{

    /**
     * ActionResponse constructor.
     * @param string|null $text
     * @param array|null $httpHeaders
     */
    public function __construct(string $text = null, array $httpHeaders = null)
    {
        $this->text = $text;
        $this->httpHeaders = $httpHeaders;
    }

    /**
     * @return string|null
     */
    public function getText( ): ?string
    {
        return $this->text;
    }

    /**
     * @return array|null
     */
    public function getHttpHeaders(): ?array
    {
        return $this->httpHeaders;
    }
}
