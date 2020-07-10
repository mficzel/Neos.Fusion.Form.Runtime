<?php
namespace Neos\Fusion\Form\Runtime\Domain;

interface SubProcessInterface extends ProcessInterface
{
    public function getLabel(): string ;

    public function isAccessible(): bool;

    public function isRequired(): bool;

    public function restoreData(array $data);

}
