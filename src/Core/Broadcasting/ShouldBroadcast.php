<?php
namespace HexaGen\Core\Broadcasting;

interface ShouldBroadcast
{
    public function broadcastOn(): array;

    public function broadcastAs(): string;

    public function broadcastWith(): array;
}
