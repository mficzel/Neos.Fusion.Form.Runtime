<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Form;

use Neos\Fusion\Form\Runtime\Domain\InvalidStepIdentifierException;
use Neos\Fusion\Form\Runtime\Domain\StepCollectionInterface;
use Neos\Fusion\Form\Runtime\Domain\StepInterface;
use Neos\Fusion\FusionObjects\DataStructureImplementation;
use Neos\Fusion\Core\Parser;

class StepCollectionImplementation extends DataStructureImplementation implements StepCollectionInterface
{
    protected $steps = [];

    /**
     * @param string $identifier
     * @return StepImplementation
     */
    public function getStepByIdentifier(string $identifier): StepInterface
    {
        if (array_key_exists($identifier, $this->steps)) {
            return $this->steps[$identifier];
        } else {
            throw new InvalidStepIdentifierException();
        }
    }

    /**
     * @return string|null
     */
    public function getFirstStepIdentifier(): ?string
    {
        return array_key_first($this->steps);
    }

    /**
     * @return string|null
     */
    public function getNextStepIdentifier(string $previousStepName): ?string
    {
        $keys = array_keys($this->steps);
        $pos = array_search($previousStepName, $keys);
        if ($pos !== false && array_key_exists($pos + 1, $keys)) {
            return $keys[$pos+1];
        }
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function evaluate()
    {
        $sortedChildFusionKeys = $this->sortNestedFusionKeys();

        $this->steps = [];

        foreach ($sortedChildFusionKeys as $key) {
            $propertyPath = $key;
            if ($this->isUntypedProperty($this->properties[$key])) {
                $propertyPath .= '<Neos.Fusion.Form:MultiStepForm.Step>';
            }
            try {
                $value = $this->fusionValue($propertyPath);
            } catch (\Exception $e) {
                $value = $this->runtime->handleRenderingException($this->path . '/' . $key, $e);
            }
            if ($value === null && $this->runtime->getLastEvaluationStatus() === Runtime::EVALUATION_SKIPPED) {
                continue;
            }
            $this->steps[$key] = $value;
        }

        return $this;
    }

    /**
     * Returns TRUE if the given property has no object type assigned
     *
     * @param mixed $property
     * @return bool
     */
    private function isUntypedProperty($property): bool
    {
        if (!is_array($property)) {
            return false;
        }
        return array_intersect_key(array_flip(Parser::$reservedParseTreeKeys), $property) === [];
    }
}
