<?php
namespace Neos\Fusion\Form\Runtime\ActionHandler;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Form\Runtime\Domain\AbstractActionHandler;
use Neos\Fusion\Form\Runtime\Domain\ActionHandlerInterface;
use Neos\Flow\Log\PsrLoggerFactoryInterface;

class LogActionHandler  extends AbstractActionHandler implements ActionHandlerInterface
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
    public function handle(array $options = []): ?string
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
