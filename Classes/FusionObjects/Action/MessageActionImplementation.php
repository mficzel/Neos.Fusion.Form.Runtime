<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Action;

use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class MessageActionImplementation extends AbstractFusionObject implements ActionInterface
{
    public function evaluate()
    {
        return $this;
    }

    public function execute($data): ?string
    {
        return $this->fusionValue('content');
    }
}
