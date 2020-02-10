<?php
namespace Neos\Fusion\Form\Runtime\Domain\ActionHandler;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

class ActionHandlerResolver
{

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param string $handlerType
     * @param array $handlerOptions
     * @return ActionInterface
     */
    public function createActionHandler(string $handlerType): ActionHandlerInterface
    {
        if ($this->objectManager->isRegistered($handlerType)) {
            $actionHandler = new $handlerType();
        } else {
            throw new NoSuchActionHandlerException('The action handler "' . $handlerType . '" was not found!', 1581362538);
        }

        if (!($actionHandler instanceof ActionHandlerInterface)) {
            throw new NoSuchActionHandlerException(sprintf('The action handler "%s" does not implement %s!', $handlerType, ActionInterface::class), 1581362552);
        }

        return $actionHandler;
    }

}
