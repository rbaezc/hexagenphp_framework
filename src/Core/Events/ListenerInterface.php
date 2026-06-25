<?php
namespace HexaGen\Core\Events;

interface ListenerInterface
{
    public function handle(Event $event): void;
}
