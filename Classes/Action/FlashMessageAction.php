<?php
namespace Neos\Fusion\Form\Runtime\Action;

use Neos\Error\Messages\Error;
use Neos\Error\Messages\Message;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Warning;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Exception\InvalidFlashMessageConfigurationException;
use Neos\Flow\Mvc\FlashMessage\FlashMessageService;
use Neos\Flow\Security\Exception\InvalidRequestPatternException;
use Neos\Flow\Security\Exception\NoRequestPatternFoundException;
use Neos\Fusion\Form\Runtime\Domain\ActionHandlerException;
use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Fusion\Form\Runtime\Domain\ActionResponseInterface;

class FlashMessageAction  implements ActionInterface
{
    /**
     * @Flow\Inject
     * @var FlashMessageService
     */
    protected $flashMessageService;

    /**
     * @param array $options
     * @return string|null
     * @throws InvalidFlashMessageConfigurationException
     * @throws InvalidRequestPatternException
     * @throws NoRequestPatternFoundException
     */
    public function handle(array $options = []): ?ActionResponseInterface
    {
        $messageBody = $options('messageBody');
        if (!is_string($messageBody)) {
            throw new ActionHandlerException(sprintf('The message body must be of type string, "%s" given.', gettype($messageBody)), 1335980069);
        }
        $messageTitle = $options('messageTitle');
        $messageArguments = $options('messageArguments');
        $messageCode = $options('messageCode');
        $severity = $options('severity');
        switch ($severity) {
            case Message::SEVERITY_NOTICE:
                $message = new Notice($messageBody, $messageCode, $messageArguments, $messageTitle);
            break;
            case Message::SEVERITY_WARNING:
                $message = new Warning($messageBody, $messageCode, $messageArguments, $messageTitle);
            break;
            case Message::SEVERITY_ERROR:
                $message = new Error($messageBody, $messageCode, $messageArguments, $messageTitle);
            break;
            default:
                $message = new Message($messageBody, $messageCode, $messageArguments, $messageTitle);
            break;
        }

        $request = $this->controllerContext->getRequest();
        $this->flashMessageService->getFlashMessageContainerForRequest($request)->addMessage($message);

        return null;
    }
}
