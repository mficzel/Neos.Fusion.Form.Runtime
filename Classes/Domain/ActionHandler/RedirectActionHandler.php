<?php
namespace Neos\Fusion\Form\Runtime\Domain\ActionHandler;

use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Flow\Mvc\Controller\ControllerContext;

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
