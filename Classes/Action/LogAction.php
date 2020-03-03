<?php
namespace Neos\Fusion\Form\Runtime\Action;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Flow\Log\PsrLoggerFactoryInterface;

class LogAction implements ActionInterface
{
    /**
     * @Flow\Inject
     * @var PsrLoggerFactoryInterface
     */
    protected $loggerFactory;

    /**
     * @param array $options
     * @return string|null
     */
    public function handle(array $options = []): ?ActionResponse
    {
        $logger = $this->loggerFactory->get($options['logger'] ?? 'systemLogger');

        $logger->log(
            $options['level'] ?? 'info',
            $options['message'] ?? '',
            $options['context'] ?? []
        );

        return null;
    }
}
