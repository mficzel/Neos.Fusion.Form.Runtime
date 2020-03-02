<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Form\Runtime\Domain\ActionHandlerInterface;
use Neos\Fusion\Form\Runtime\Domain\ActionResolver;
use Neos\Fusion\FusionObjects\DataStructureImplementation;

class DataStructureActionHandlerImplementation extends DataStructureImplementation implements ActionHandlerInterface
{

    /**
     * @var ActionResolver
     * @Flow\Inject
     */
    protected $actionResolver;

    public function evaluate()
    {
        return $this;
    }

    public function handle(array $data = [], ControllerContext $controllerContext): ?string
    {
        $this->getRuntime()->pushContext('data', $data);
        $actionConfigurations = parent::evaluate();
        $this->getRuntime()->popContext();

        $messages = [];
        $headers = [];
        foreach ($actionConfigurations as $actionConfiguration) {
            $action = $this->actionResolver->createAction($actionConfiguration['identifier']);
            $response = $action->handle($actionConfiguration['options'] ?? []);
            if ($response) {
                if ($response->getText()) {
                    $messages[] = $response->getText();
                }
                if ($response->getHttpHeaders()) {
                    $headers = array_merge($headers, $response->getHttpHeaders());
                }
            }
        }

        if ($headers) {
            foreach ($headers as $key => $value) {
                $response = $controllerContext->getResponse();
                $response->setComponentParameter(SetHeaderComponent::class, $key, $value);
            }
        }

        if ($messages) {
            return  implode('', $messages);
        }

        return null;
    }

}
