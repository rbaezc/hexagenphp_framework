<?php
namespace HexaGen\Core\Mail;

interface MailDriverInterface
{
    public function send(Mailable $mailable): void;
}
