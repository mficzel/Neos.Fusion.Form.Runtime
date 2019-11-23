<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects;

use Neos\Fusion\Form\Runtime\Domain\SinglePageForm;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class SinglePageFormImplementation extends AbstractFusionObject
{

    public function evaluate()
    {
        $form = new SinglePageForm(
            $this->getRuntime()->getControllerContext()->getRequest(),
            $this->fusionValue('identifier'),
            $this->fusionValue('validator')
        );

        $this->getRuntime()->pushContext('form', $form);

        if (empty($form->getData()) || $form->hasErrors()) {
            $this->getRuntime()->pushContext('content', $this->fusionValue('form'));
            $result = $this->fusionValue('renderer');
            $this->getRuntime()->popContext();
        } else {
            $result = $this->fusionValue('finisher');
        }

        $this->getRuntime()->popContext();
        return $result;
    }


}
