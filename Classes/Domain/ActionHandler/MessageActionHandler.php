<?php
namespace Neos\Fusion\Form\Runtime\Domain\ActionHandler;

use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Form\Runtime\Domain\ActionHandler\ActionHandlerInterface;

class MessageActionHandler implements ActionHandlerInterface
{
    public function handle(ControllerContext $controllerContext, array $options = []): ?string
    {
        return $options['content'];
    }
}
