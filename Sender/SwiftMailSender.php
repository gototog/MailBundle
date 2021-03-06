<?php

namespace Extellient\MailBundle\Sender;

use Extellient\MailBundle\Entity\MailInterface;
use Extellient\MailBundle\Exception\MailerSenderEmptyException;
use Extellient\MailBundle\Exception\MailSenderException;
use Extellient\MailBundle\Provider\Mail\MailProviderInterface;
use Swift_Attachment;
use Swift_Mailer;
use Swift_Message;

/**
 * Class MailSenderService.
 */
class SwiftMailSender implements MailSenderInterface
{
    /**
     * @var Swift_Mailer
     */
    private $mailer;
    /**
     * @var MailProviderInterface
     */
    private $mailEntityProvider;

    /**
     * MailSenderService constructor.
     *
     * @param Swift_Mailer          $mailer
     * @param MailProviderInterface $mailEntityProvider
     */
    public function __construct(Swift_Mailer $mailer, MailProviderInterface $mailEntityProvider)
    {
        $this->mailer = $mailer;
        $this->mailEntityProvider = $mailEntityProvider;
    }

    /**
     * @param MailInterface $mail
     *
     * @return Swift_Message
     *
     * @throws MailerSenderEmptyException
     */
    public function initSwiftMessage(MailInterface $mail)
    {
        $message = (new Swift_Message($mail->getSubject()))
            ->setTo($mail->getRecipient())
            ->setBody($mail->getBody(), 'text/html')
            ->setCc($mail->getRecipientCopy())
            ->setBcc($mail->getRecipientHiddenCopy());

        if (empty($mail->getSenderEmail())) {
            throw new MailerSenderEmptyException($mail);
        }

        $senderAlias = !empty($mail->getSenderAlias()) ? $mail->getSenderAlias() : null;
        $message->setFrom($mail->getSenderEmail(), $senderAlias);

        foreach ($mail->getAttachements() as $attachement) {
            $message->attach(Swift_Attachment::fromPath($attachement));
        }

        return $message;
    }

    /**
     * @param MailInterface $mail
     *
     * @return int
     *
     * @throws MailSenderException
     * @throws MailerSenderEmptyException
     */
    public function send(MailInterface $mail)
    {
        $failedRecipient = [];

        $message = $this->initSwiftMessage($mail);

        $sent = $this->mailer->send($message, $failedRecipient);

        if (!empty($failedRecipient)) {
            throw new MailSenderException();
        }

        return $sent;
    }
}
