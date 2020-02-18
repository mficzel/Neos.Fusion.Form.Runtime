<?php
namespace Neos\Fusion\Form\Runtime\ActionHandler;

use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Fusion\Form\Runtime\Domain\AbstractActionHandler;
use Neos\Fusion\Form\Runtime\Domain\ActionHandlerInterface;

class RedirectActionHandler extends AbstractActionHandler implements ActionHandlerInterface
{
    public function handle(array $options = []): ?string
    {
        $uri = $options['uri'];
        if ($uri) {
            $response = $this->controllerContext->getResponse();
            $response->setComponentParameter(SetHeaderComponent::class, 'Location', $uri);
            $response->setStatusCode(301);
        }
        return null;
    }

}
