<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Action;

use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Fusion\FusionObjects\DataStructureImplementation;

class ActionListImplementation extends DataStructureImplementation implements ActionInterface
{
    public function evaluate()
    {
        return $this;
    }

    public function execute($data): ?string
    {
        $results = [];
        $sortedChildFusionKeys = $this->sortNestedFusionKeys();
        foreach ($sortedChildFusionKeys as $key) {
            $subAction = $this->fusionValue($key);
            if ($subAction instanceof ActionInterface) {
                $results[] = $subAction->execute($data);
            }
        }
        $results = array_filter($results);
        if ($results) {
            return implode('', $results);
        }
        return null;
    }
}
