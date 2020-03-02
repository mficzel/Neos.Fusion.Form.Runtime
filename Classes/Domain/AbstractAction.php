<?php
namespace Neos\Fusion\Form\Runtime\Domain;

use Neos\Flow\Mvc\Controller\ControllerContext;

abstract class AbstractAction implements ActionInterface
{
    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @param ControllerContext $controllerContext
     */
    public function setControllerContext(ControllerContext $controllerContext): void
    {
        $this->controllerContext = $controllerContext;
    }
}
