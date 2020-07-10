<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects;

use Neos\Fusion\Form\Domain\Form;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Fusion\Form\Runtime\Domain\ProcessInterface;

class RuntimeFormImplementation  extends AbstractFusionObject
{
    /**
     * @return string
     */
    protected function getIdentifier(): string
    {
        return $this->fusionValue('identifier');
    }

    /**
     * @return ProcessInterface
     */
    protected function getProcess(): ProcessInterface
    {
        return $this->fusionValue('process');
    }

    /**
     * @return ActionInterface
     */
    protected function getAction(): ActionInterface
    {
        return  $this->fusionValue('action');
    }

    /**
     * @return string
     */
    public function evaluate(): string
    {
        $identifier = $this->getIdentifier();
        $process = $this->getProcess();

        //
        // send values to the current process
        // until and call the renderForm method
        // until the process is finished
        //
        $process->setIdentifier($identifier);
        $unvalidatedData = $this->getSubmittedData($identifier);
        if ($unvalidatedData) {
            $process->submitData($unvalidatedData);
        }
        if (!$process->isCompleted()) {
            $this->getRuntime()->pushContextArray([
                'form' => $process->getForm(),
                'data' => $process->getData()
            ]);
            $this->getRuntime()->pushContext('content', $process->render());
            $result = $this->fusionValue('renderer');
            $this->getRuntime()->popContext();
            $this->getRuntime()->popContext();
            return $result;
        }

        //
        // the content of the action response is returned directly
        // everything else is merged into the parent response
        //
        $action = $this->getAction();
        $actionResponse = $action->handle($process->getData());
        if ($actionResponse) {
            $content = $actionResponse->getContent();
            $actionResponse->setContent('');
            $actionResponse->mergeIntoParentResponse($this->getRuntime()->getControllerContext()->getResponse());
            return $content;
        } else {
            return '';
        }
    }

    /**
     * @param string $formIdentifier
     * @return array
     */
    protected function getSubmittedData(string $formIdentifier): array
    {
        $request = $this->getRuntime()->getControllerContext()->getRequest();
        if ($request->hasArgument($formIdentifier)) {
            $submittedValues = $request->getArgument($formIdentifier);
            if (is_array($submittedValues)) {
                $submittedValues = array_map(
                    function($item) {
                        if ($item instanceof UploadedFileInterface) {
                            return SerializableUploadedFile::fromUploadedFile($item);
                        } else {
                            return $item;
                        }
                    },
                    $submittedValues
                );
            }
            return $submittedValues;
        }
        return [];
    }
}
