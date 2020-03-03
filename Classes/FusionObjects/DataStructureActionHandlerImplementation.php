<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Fusion\Form\Runtime\Domain\ActionResolver;
use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Fusion\FusionObjects\DataStructureImplementation;

class DataStructureActionHandlerImplementation extends DataStructureImplementation implements ActionInterface
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

    public function handle(array $data = []): ?ActionResponse
    {
        $this->getRuntime()->pushContext('data', $data);
        $actionConfigurations = parent::evaluate();
        $this->getRuntime()->popContext();

        $response = new ActionResponse();
        foreach ($actionConfigurations as $actionConfiguration) {
            $subAction = $this->actionResolver->createAction($actionConfiguration['identifier']);
            $subActionResponse = $subAction->handle($actionConfiguration['options'] ?? []);

            if ($subActionResponse) {
                // content of multiple responses is concatenated
                if ($subActionResponse->getContent()) {
                    $mergedContent = $response->getContent() . $subActionResponse->getContent();
                    $subActionResponse->setContent($mergedContent);
                }
                // preserve non 200 status codes that would be overwritten
                if ($response->getStatusCode() !== 200 && $subActionResponse->getStatusCode() == 200) {
                    $subActionResponse->setStatusCode($response->getStatusCode());
                }
                $subActionResponse->mergeIntoParentResponse($response);
            }
        }
        return $response;
    }
}
