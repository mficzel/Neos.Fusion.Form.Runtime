<?php
namespace Neos\Fusion\Form\Runtime\ActionHandler;

use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Form\Runtime\Domain\ActionHandlerInterface;

class RedirectActionHandler implements ActionHandlerInterface
{

    public function handle(ControllerContext $controllerContext, array $options = []): ?string
    {
        $uri = $options['uri'];
        if ($uri) {
            $response = $controllerContext->getResponse();
            $response->setComponentParameter(SetHeaderComponent::class, 'Location', $uri);
            $response->setStatusCode(301);
        }
        return null;
    }

}
