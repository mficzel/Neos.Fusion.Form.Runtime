<?php
namespace Neos\Fusion\Form\Runtime\Action;

use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Fusion\Form\Runtime\Domain\ActionResponse;
use Neos\Fusion\Form\Runtime\Domain\ActionResponseInterface;

class MessageAction implements ActionInterface
{
    public function handle(array $options = []): ?ActionResponseInterface
    {
        return new ActionResponse($options['message']);
    }
}
