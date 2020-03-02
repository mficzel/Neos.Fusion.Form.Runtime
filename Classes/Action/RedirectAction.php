<?php
namespace Neos\Fusion\Form\Runtime\Action;

use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Fusion\Form\Runtime\Domain\AbstractAction;
use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Fusion\Form\Runtime\Domain\ActionResponse;
use Neos\Fusion\Form\Runtime\Domain\ActionResponseInterface;

class RedirectAction extends AbstractAction implements ActionInterface
{
    public function handle(array $options = []): ?ActionResponseInterface
    {
        $uri = $options['uri'];
        $status = $options['status'] ?? 303;

        if ($uri) {
            return new ActionResponse(null, ['Status' => $status, 'Location' => $uri]);
        }

        return null;
    }

}
