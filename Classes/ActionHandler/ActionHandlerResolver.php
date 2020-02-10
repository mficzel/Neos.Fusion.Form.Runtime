<?php
namespace Neos\Fusion\Form\Runtime\ActionHandler;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Validation\Validator\ValidatorInterface;

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
        if ($objectName = $this->resolveActionHandlerObjectName($handlerType)) {
            $actionHandler = new $objectName();
        } else {
            throw new NoSuchActionHandlerException('The action handler "' . $handlerType . '" was could not be resolved!', 1581362538);
        }

        if (!($actionHandler instanceof ActionHandlerInterface)) {
            throw new NoSuchActionHandlerException(sprintf('The action handler "%s" does not implement %s!', $handlerType, ActionHandlerInterface::class), 1581362552);
        }

        return $actionHandler;
    }

    /**
     * @param string $handlerType Either the fully qualified class name of the action handler or the short name
     * @return string|boolean Class name of the action handler or false if not available
     */
    protected function resolveActionHandlerObjectName($handlerType)
    {
        $handlerType = ltrim($handlerType, '\\');

        if ($this->objectManager->isRegistered($handlerType)) {
            return $handlerType;
        }

        if (strpos($handlerType, ':') !== false) {
            list($packageName, $packageActionHandlerType) = explode(':', $handlerType);
            $possibleClassName = sprintf(
                '%s\ActionHandler\%sActionHandler',
                str_replace('.', '\\', $packageName),
                str_replace('.', '\\', $packageActionHandlerType)
            );
            if ($this->objectManager->isRegistered($possibleClassName)) {
                return $possibleClassName;
            }
        }

        return false;
    }
}
