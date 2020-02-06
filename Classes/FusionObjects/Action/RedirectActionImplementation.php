<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Action;

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class RedirectActionImplementation extends AbstractFusionObject implements ActionInterface
{
    public function evaluate()
    {
        return $this;
    }

    public function execute($data): ?string
    {
        $uri = $this->fusionValue('uri');
        if ($uri) {
            $response = $this->getRuntime()->getControllerContext()->getResponse();
            $response->setComponentParameter(SetHeaderComponent::class, 'Location', $uri);
            $response->setStatusCode(301);
        }
        return null;
    }
}
