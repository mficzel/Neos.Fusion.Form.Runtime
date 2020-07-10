<?php
namespace Neos\Fusion\Form\Runtime\Action;

use Neos\Flow\Mvc\ActionResponse;
use Neos\Fusion\Form\Runtime\Domain\Exception\ActionException;
use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Flow\Http\Component\SetHeaderComponent;

class RedirectAction implements ActionInterface
{
    public function handle(array $options = []): ?ActionResponse
    {
        $uri = $options['uri'];

        if (!$uri) {
            throw new ActionException('No uri for redirect action was define.', 1583249244);
        }

        $status = $options['status'] ?? 303;

        $response = new ActionResponse();
        $response->setComponentParameter(SetHeaderComponent::class, 'Location', $uri);
        $response->setStatusCode($status);
        return $response;
    }

}
