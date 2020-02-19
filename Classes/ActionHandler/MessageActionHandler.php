<?php
namespace Neos\Fusion\Form\Runtime\ActionHandler;

use Neos\Fusion\Form\Runtime\Domain\AbstractActionHandler;
use Neos\Fusion\Form\Runtime\Domain\ActionHandlerInterface;

class MessageActionHandler extends AbstractActionHandler implements ActionHandlerInterface
{
    public function handle(array $options = []): ?string
    {
        return $options['message'];
    }
}
