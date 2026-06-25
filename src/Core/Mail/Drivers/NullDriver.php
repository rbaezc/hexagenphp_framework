<?php
namespace HexaGen\Core\Mail\Drivers;

use HexaGen\Core\Mail\Mailable;
use HexaGen\Core\Mail\MailDriverInterface;

class NullDriver implements MailDriverInterface
{
    public function send(Mailable $mailable): void
    {
        // Intentionally discards all mail (useful for testing)
    }
}
