<?php
namespace HexaGen\Core\Mail\Drivers;

use HexaGen\Core\Mail\Mailable;
use HexaGen\Core\Mail\MailDriverInterface;

class LogDriver implements MailDriverInterface
{
    public function send(Mailable $mailable): void
    {
        $to      = implode(', ', array_column($mailable->getToAddresses(), 'address'));
        $subject = $mailable->getSubject();
        $from    = $mailable->getFromAddress();
        $body    = $mailable->getHtmlBody() ?: $mailable->getTextBody();

        $message = sprintf(
            "[Mail] From: %s | To: %s | Subject: %s\n%s",
            $from, $to, $subject, $body
        );

        \HexaGen\Core\Log\Logger::channel('default')->debug($message);
    }
}
