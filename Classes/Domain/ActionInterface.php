<?php
namespace Neos\Fusion\Form\Runtime\Domain;

interface ActionInterface
{
    public function execute($data): ?string;
}
