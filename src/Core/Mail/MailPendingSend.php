<?php
namespace HexaGen\Core\Mail;

class MailPendingSend
{
    private array $to  = [];
    private array $cc  = [];
    private array $bcc = [];

    public function __construct(private MailDriverInterface $driver) {}

    public function to(string|array $address, string $name = ''): static
    {
        foreach ((array) $address as $addr) {
            $this->to[] = is_array($addr) ? $addr : ['address' => $addr, 'name' => $name];
        }
        return $this;
    }

    public function cc(string|array $address, string $name = ''): static
    {
        foreach ((array) $address as $addr) {
            $this->cc[] = is_array($addr) ? $addr : ['address' => $addr, 'name' => $name];
        }
        return $this;
    }

    public function bcc(string|array $address, string $name = ''): static
    {
        foreach ((array) $address as $addr) {
            $this->bcc[] = is_array($addr) ? $addr : ['address' => $addr, 'name' => $name];
        }
        return $this;
    }

    public function send(Mailable $mailable): void
    {
        $mailable->build();
        foreach ($this->to  as $addr) { $mailable->to($addr['address'],  $addr['name'] ?? ''); }
        foreach ($this->cc  as $addr) { $mailable->cc($addr['address'],  $addr['name'] ?? ''); }
        foreach ($this->bcc as $addr) { $mailable->bcc($addr['address'], $addr['name'] ?? ''); }
        $this->driver->send($mailable);
    }

    public function queue(Mailable $mailable, ?string $queue = null): void
    {
        $pending = $this;
        $job = new class($pending, $mailable) extends \HexaGen\Core\Queue\Job {
            public function __construct(
                private MailPendingSend $pending,
                private Mailable $mailable
            ) {}
            public function handle(): void { $this->pending->send($this->mailable); }
        };
        if ($queue) {
            $job->onQueue($queue);
        }
        \HexaGen\Core\Queue\QueueManager::push($job);
    }
}
