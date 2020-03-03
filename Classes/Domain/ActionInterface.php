<?php
namespace Neos\Fusion\Form\Runtime\Domain;

use Neos\Flow\Mvc\ActionResponse;

interface ActionInterface
{
    /**
     * @param array $options
     * @return string|null
     */
    public function handle(array $options = []): ?ActionResponse;

}
