<?php
namespace Neos\Fusion\Form\Runtime\Domain;

use Neos\Flow\Mvc\Controller\ControllerContext;

interface ActionHandlerInterface
{
    /**
     * @param array $options
     * @return string|null
     */
    public function handle(array $options = []): ?string;

    /**
     * @param ControllerContext $controllerContext
     */
    public function setControllerContext(ControllerContext $controllerContext): void;
}
