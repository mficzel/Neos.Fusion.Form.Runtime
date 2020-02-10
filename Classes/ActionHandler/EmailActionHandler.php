<?php
namespace Neos\Fusion\Form\Runtime\ActionHandler;

use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\SwiftMailer\Message as SwiftMailerMessage;

class EmailActionHandler implements ActionHandlerInterface
{
    public function handle(ControllerContext $controllerContext, array $options = []): ?string
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
