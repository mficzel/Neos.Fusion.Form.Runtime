<?php
namespace Neos\Fusion\Form\Runtime\Domain;

use Neos\Flow\Http\Headers;
use Neos\Flow\Mvc\Controller\ControllerContext;

interface ActionResponseInterface
{
    /**
     * @return string|null
     */
    public function getText(): ?string;

    /**
     * @return array|null
     */
    public function getHttpHeaders(): ?array;

}
