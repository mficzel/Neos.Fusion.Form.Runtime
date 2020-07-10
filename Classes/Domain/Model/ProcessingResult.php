<?php
namespace Neos\Fusion\Form\Runtime\Domain\Model;

use Neos\Error\Messages\Result;
use Neos\Fusion\Form\Runtime\Domain\ProcessingResultInterface;

class ProcessingResult implements ProcessingResultInterface
{

    /**
     * @var array
     */
    protected $data;

    /**
     * @var Result
     */
    protected $result;

    /**
     * ProcessingResult constructor.
     * @param array $data
     * @param Result|null $result
     */
    public function __construct(array $data = [], Result $result = null)
    {
        $this->data = $data;
        $this->result = $result ?? new Result();
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return Result
     */
    public function getResult(): Result
    {
        return $this->result;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->getResult()->hasErrors();
    }
}
