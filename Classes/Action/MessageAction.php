<?php
namespace Neos\Fusion\Form\Runtime\Action;

use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Flow\Mvc\ActionResponse;

class MessageAction implements ActionInterface
{
    public function handle(array $options = []): ?ActionResponse
    {
        $response = new ActionResponse();
        $response->setContent($options['message']);
        return $response;
    }
}
