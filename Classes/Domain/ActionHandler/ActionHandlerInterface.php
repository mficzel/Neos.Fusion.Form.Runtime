<?php
namespace Neos\Fusion\Form\Runtime\Domain\ActionHandler;

use Neos\Flow\Mvc\Controller\ControllerContext;

interface ActionHandlerInterface
{
    public function handle(ControllerContext $controllerContext, array $options = []): ?string;
}
