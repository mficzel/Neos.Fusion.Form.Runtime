<?php
namespace Neos\Fusion\Form\Runtime\Domain;

use Neos\Flow\Mvc\Controller\ControllerContext;

interface ActionInterface
{
    /**
     * @param array $options
     * @return string|null
     */
    public function handle(array $options = []): ?ActionResponseInterface;

}
