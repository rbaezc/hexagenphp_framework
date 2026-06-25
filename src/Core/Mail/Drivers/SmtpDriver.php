<?php
namespace HexaGen\Core\Mail\Drivers;

use HexaGen\Core\Mail\Mailable;
use HexaGen\Core\Mail\MailDriverInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class SmtpDriver implements MailDriverInterface
{
    public function send(Mailable $mailable): void
    {
        $host     = \HexaGen\Core\Config::get('mail.smtp.host', 'localhost');
        $port     = \HexaGen\Core\Config::get('mail.smtp.port', 587);
        $user     = \HexaGen\Core\Config::get('mail.smtp.username', '');
        $password = \HexaGen\Core\Config::get('mail.smtp.password', '');
        $enc      = \HexaGen\Core\Config::get('mail.smtp.encryption', 'tls');

        $scheme  = match($enc) { 'ssl' => 'smtps', 'tls' => 'smtp', default => 'smtp' };
        $dsn     = $user
            ? "{$scheme}://{$user}:{$password}@{$host}:{$port}"
            : "{$scheme}://{$host}:{$port}";

        $transport = Transport::fromDsn($dsn);
        $mailer    = new Mailer($transport);

        $email = (new Email())
            ->from(new Address($mailable->getFromAddress(), $mailable->getFromName()))
            ->subject($mailable->getSubject());

        foreach ($mailable->getToAddresses() as $to) {
            $email->addTo(new Address($to['address'], $to['name'] ?? ''));
        }
        foreach ($mailable->getCcAddresses() as $cc) {
            $email->addCc(new Address($cc['address'], $cc['name'] ?? ''));
        }
        foreach ($mailable->getBccAddresses() as $bcc) {
            $email->addBcc(new Address($bcc['address'], $bcc['name'] ?? ''));
        }
        foreach ($mailable->getReplyToAddresses() as $rt) {
            $email->addReplyTo(new Address($rt['address'], $rt['name'] ?? ''));
        }

        $html = $mailable->getHtmlBody();
        if ($html !== '') {
            $email->html($html);
        }
        $text = $mailable->getTextBody();
        if ($text !== '') {
            $email->text($text);
        }

        foreach ($mailable->getAttachments() as $attachment) {
            $email->attachFromPath($attachment['path'], $attachment['name'] ?? null, $attachment['mime'] ?? null);
        }

        $mailer->send($email);
    }
}
