<?php
namespace Neos\Fusion\Form\Runtime\ActionHandler;

use Neos\Flow\Mvc\Controller\ControllerContext;

class MessageActionHandler implements ActionHandlerInterface
{
    public function handle(ControllerContext $controllerContext, array $options = []): ?string
    {
        return $options['content'];
    }
}
