<?php
namespace Neos\Fusion\Form\Runtime\Action;

use Neos\Flow\Mvc\ActionResponse;
use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Flow\Http\Component\SetHeaderComponent;

class RedirectAction implements ActionInterface
{
    public function handle(array $options = []): ?ActionResponse
    {
        $uri = $options['uri'];
        $status = $options['status'] ?? 303;

        if ($uri) {
            $response = new ActionResponse();
            $response->setComponentParameter(SetHeaderComponent::class, 'Location', $uri);
            $response->setStatusCode($status);
            return $response;
        }

        return null;
    }

}
