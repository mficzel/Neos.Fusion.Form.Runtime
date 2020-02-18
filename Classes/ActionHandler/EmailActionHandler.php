<?php
namespace Neos\Fusion\Form\Runtime\ActionHandler;

use Neos\Fusion\Form\Runtime\Domain\AbstractActionHandler;
use Neos\Fusion\Form\Runtime\Domain\ActionHandlerInterface;
use Neos\SwiftMailer\Message as SwiftMailerMessage;

class EmailActionHandler extends AbstractActionHandler implements ActionHandlerInterface
{
    public function handle(array $options = []): ?string
    {
        $to = $options['to'];
        $from = $options['from'];
        $subject = $options['subject'];
        $text = $options['text'];

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
