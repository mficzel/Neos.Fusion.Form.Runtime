<?php
namespace Neos\Fusion\Form\Runtime\FusionObjects\Action;

use Neos\Fusion\Form\Runtime\Domain\ActionInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\SwiftMailer\Message as SwiftMailerMessage;

class EmailActionImplementation extends AbstractFusionObject implements ActionInterface
{
    public function evaluate()
    {
        return $this;
    }

    public function execute($data): ?string
    {
        $to = $this->fusionValue('to');
        $from = $this->fusionValue('from');
        $subject = $this->fusionValue('subject');
        $text = $this->fusionValue('text');

        $mail = new SwiftMailerMessage();

        $mail
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($text, 'text/plain');

        $mail->send();

        return null;
    }
}
