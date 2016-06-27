<?php
/**
 * Created by Feki Webstudio - 2016. 06. 27. 13:51
 * @author Zsolt
 * @copyright Copyright (c) 2016, Feki Webstudio Kft.
 */

namespace FekiWebstudio\PreviewMail;

use Illuminate\Mail\Transport\Transport;
use Swift_Mime_Message;

/**
 * Class PreviewMailTransport defines a driver to use on
 * the preview server to fake email recipients.
 *
 * @package FekiWebstudio\PreviewMail
 */
class PreviewMailTransport extends Transport
{
    /**
     * Array of e-mail addresses to be set
     * as recipients.
     *
     * @var array
     */
    protected $recipients;

    /**
     * PreviewMailTransport constructor.
     *
     * @param array|string $recipients
     */
    public function __construct($recipients)
    {
        $this->recipients = $recipients;
    }

    /**
     * Set the fake addresses and send the e-mail.
     *
     * @param Swift_Mime_Message $message
     * @param mixed &$failedRecipients
     * @return int
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $this->includeRecipientInSubject($message);

        foreach ($this->recipients as $recipient) {
            mail($recipient, $message->getSubject(), $message->toString());
        }

        if (is_array($this->recipients)) {
            return count($this->recipients);
        }

        return 1;
    }

    /**
     * @inheritdoc
     */
    protected function includeRecipientInSubject(Swift_Mime_Message $message)
    {
        $originalRecipients = array_keys($message->getTo());
        $recipientList = implode(", ", $originalRecipients);

        // Include real recipient in the subject
        $subject = sprintf(
            "%s [Eredeti címzettek: %s]",
            $message->getSubject(),
            $recipientList
        );

        $message->setSubject($subject);
    }
}