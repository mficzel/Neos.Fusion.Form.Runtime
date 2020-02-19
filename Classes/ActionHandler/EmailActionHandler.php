<?php
namespace Neos\Fusion\Form\Runtime\ActionHandler;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Form\Exception\FinisherException;
use Neos\Fusion\Form\Runtime\Domain\AbstractActionHandler;
use Neos\Fusion\Form\Runtime\Domain\ActionHandlerException;
use Neos\Fusion\Form\Runtime\Domain\ActionHandlerInterface;
use Neos\SwiftMailer\Message as SwiftMailerMessage;
use Neos\Utility\ObjectAccess;
use Neos\Utility\MediaTypes;

class EmailActionHandler extends AbstractActionHandler implements ActionHandlerInterface
{

    /**
     * @param array $options
     * @return string|null
     * @throws ActionHandlerException
     * @throws FinisherException
     */
    public function handle(array $options = []): ?string
    {
        if (!class_exists(SwiftMailerMessage::class)) {
            throw new ActionHandlerException('The "neos/swiftmailer" doesn\'t seem to be installed, but is required for the EmailFinisher to work!', 1503392532);
        }

        $subject = $options['subject'] ?? null;
        $text = $options['text'] ?? null;
        $html = $options['html'] ?? null;

        $recipientAddress = $options['recipientAddress'] ?? null;
        $recipientName = $options['recipientName'] ?? null;
        $senderAddress = $options['senderAddress'] ?? null;
        $senderName = $options['senderName'] ?? null;
        $replyToAddress = $options['replyToAddress'] ?? null;
        $carbonCopyAddress = $options['carbonCopyAddress'] ?? null;
        $blindCarbonCopyAddress = $options['blindCarbonCopyAddress'] ?? null;

        $testMode = $options['testMode'] ?? false;

        if ($subject === null) {
            throw new ActionHandlerException('The option "subject" must be set for the EmailFinisher.', 1327060320);
        }
        if ($recipientAddress === null) {
            throw new ActionHandlerException('The option "recipientAddress" must be set for the EmailFinisher.', 1327060200);
        }
        if (is_array($recipientAddress) && !empty($recipientName)) {
            throw new ActionHandlerException('The option "recipientName" cannot be used with multiple recipients in the EmailFinisher.', 1483365977);
        }
        if ($senderAddress === null) {
            throw new ActionHandlerException('The option "senderAddress" must be set for the EmailFinisher.', 1327060210);
        }

        $mail = new SwiftMailerMessage();

        $mail
            ->setFrom($senderName ? array($senderAddress => $senderName) : $senderAddress)
            ->setSubject($subject);

        if (is_array($recipientAddress)) {
            $mail->setTo($recipientAddress);
        } else {
            $mail->setTo($recipientName ? array($recipientAddress => $recipientName) : $recipientAddress);
        }

        if ($replyToAddress !== null) {
            $mail->setReplyTo($replyToAddress);
        }

        if ($carbonCopyAddress !== null) {
            $mail->setCc($carbonCopyAddress);
        }

        if ($blindCarbonCopyAddress !== null) {
            $mail->setBcc($blindCarbonCopyAddress);
        }

        if ($text !== null && $html !== null) {
            $mail->setBody($html, 'text/html');
            $mail->addPart($text, 'text/plain');
        } else if ($text !== null ) {
            $mail->setBody($text, 'text/plain');
        } else if ($html !== null) {
            $mail->setBody($html, 'text/html');
        }

        $this->addAttachments($mail, $options);

        if ($testMode === true) {
            \Neos\Flow\var_dump(
                array(
                    'sender' => array($senderAddress => $senderName),
                    'recipients' => is_array($recipientAddress) ? $recipientAddress : array($recipientAddress => $recipientName),
                    'replyToAddress' => $replyToAddress,
                    'carbonCopyAddress' => $carbonCopyAddress,
                    'blindCarbonCopyAddress' => $blindCarbonCopyAddress,
                    'text' => $text,
                    'html' => $html
                ),
                'E-Mail "' . $subject . '"'
            );
        } else {
            $mail->send();
        }

        return null;
    }

    /**
     * @param SwiftMailerMessage $mail
     * @param array $options
     */
    protected function addAttachments(SwiftMailerMessage $mail, array $options)
    {
        $attachmentConfigurations = $options['attachments'] ?? null;
        if (is_array($attachmentConfigurations)) {
            foreach ($attachmentConfigurations as $attachmentConfiguration) {
                if (isset($attachmentConfiguration['path'])) {
                    $mail->attach(\Swift_Attachment::fromPath($attachmentConfiguration['path']));
                    continue;
                } else if (isset($attachmentConfiguration['field'])) {
                    $resource = $attachmentConfiguration['field'];
                    if (!$resource instanceof PersistentResource) {
                        continue;
                    }
                    $mail->attach(new \Swift_Attachment(stream_get_contents($resource->getStream()), $resource->getFilename(), $resource->getMediaType()));
                    continue;
                } else if (isset($attachmentConfiguration['content']) && isset($attachmentConfiguration['name'])) {
                    $content = $attachmentConfiguration['content'];
                    $name = $attachmentConfiguration['name'];
                    $type =  $attachmentConfiguration['type'] ?? MediaTypes::getMediaTypeFromFilename($name);
                    $mail->attach(new \Swift_Attachment($content, $name, $type));
                    continue;
                }
            }
        }
    }
}
