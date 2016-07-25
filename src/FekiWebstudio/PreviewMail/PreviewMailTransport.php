<?php
/**
 * Created by Feki Webstudio - 2016. 06. 27. 13:51
 * @author Zsolt
 * @copyright Copyright (c) 2016, Feki Webstudio Kft.
 */

namespace FekiWebstudio\PreviewMail;

use Illuminate\Mail\Transport\Transport;
use Swift_Mime_Message;
use Swift_Transport_MailInvoker;

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

    /** Additional parameters to pass to mail() */
    private $_extraParams = '-f%s';

    /** An invoker that calls the mail() function */
    private $_invoker;

    /**
     * PreviewMailTransport constructor.
     *
     * @param array|string $recipients
     * @param Swift_Transport_MailInvoker $invoker
     */
    public function __construct($recipients, Swift_Transport_MailInvoker $invoker)
    {
        $this->recipients = $recipients;
        $this->_invoker = $invoker;
    }

    /**
     * Not used.
     */
    public function isStarted()
    {
        return false;
    }

    /**
     * Not used.
     */
    public function start()
    {
    }

    /**
     * Not used.
     */
    public function stop()
    {
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

        $subjectHeader = $message->getHeaders()->get('Subject');
        $subject = $subjectHeader ? $subjectHeader->getFieldBody() : '';

        // Remove headers that would otherwise be duplicated
        $message->getHeaders()->remove('Subject');

        foreach ($this->recipients as $recipient) {
            $messageStr = $message->toString();
            $to = $recipient;
            $message->getHeaders()->remove('To');

            // Separate headers from body
            if (false !== $endHeaders = strpos($messageStr, "\r\n\r\n")) {
                $headers = substr($messageStr, 0, $endHeaders)."\r\n"; //Keep last EOL
                $body = substr($messageStr, $endHeaders + 4);
            } else {
                $headers = $messageStr."\r\n";
                $body = '';
            }

            if ("\r\n" != PHP_EOL) {
                // Non-windows (not using SMTP)
                $headers = str_replace("\r\n", PHP_EOL, $headers);
                $subject = str_replace("\r\n", PHP_EOL, $subject);
                $body = str_replace("\r\n", PHP_EOL, $body);
            } else {
                // Windows, using SMTP
                $headers = str_replace("\r\n.", "\r\n..", $headers);
                $subject = str_replace("\r\n.", "\r\n..", $subject);
                $body = str_replace("\r\n.", "\r\n..", $body);
            }

            $this->_invoker->mail($to, $subject, $body, $headers, $this->_formatExtraParams($this->_extraParams, null));
        }

        return 1;
    }

    /**
     * Return php mail extra params to use for invoker->mail.
     *
     * @param $extraParams
     * @param $reversePath
     *
     * @return string|null
     */
    private function _formatExtraParams($extraParams, $reversePath)
    {
        if (false !== strpos($extraParams, '-f%s')) {
            $extraParams = empty($reversePath) ? str_replace('-f%s', '', $extraParams) : sprintf($extraParams, escapeshellarg($reversePath));
        }

        return !empty($extraParams) ? $extraParams : null;
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