<?php
namespace HexaGen\Core\Mail;

abstract class Mailable
{
    protected string $subject = '';
    protected string $fromAddress = '';
    protected string $fromName = '';
    protected array $toAddresses = [];
    protected array $ccAddresses = [];
    protected array $bccAddresses = [];
    protected array $replyToAddresses = [];
    protected array $attachments = [];
    protected string $htmlBody = '';
    protected string $textBody = '';
    protected ?string $view = null;
    protected array $viewData = [];

    abstract public function build(): static;

    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function from(string $address, string $name = ''): static
    {
        $this->fromAddress = $address;
        $this->fromName    = $name;
        return $this;
    }

    public function to(string|array $address, string $name = ''): static
    {
        foreach ((array) $address as $addr) {
            $this->toAddresses[] = is_array($addr) ? $addr : ['address' => $addr, 'name' => $name];
        }
        return $this;
    }

    public function cc(string|array $address, string $name = ''): static
    {
        foreach ((array) $address as $addr) {
            $this->ccAddresses[] = is_array($addr) ? $addr : ['address' => $addr, 'name' => $name];
        }
        return $this;
    }

    public function bcc(string|array $address, string $name = ''): static
    {
        foreach ((array) $address as $addr) {
            $this->bccAddresses[] = is_array($addr) ? $addr : ['address' => $addr, 'name' => $name];
        }
        return $this;
    }

    public function replyTo(string $address, string $name = ''): static
    {
        $this->replyToAddresses[] = ['address' => $address, 'name' => $name];
        return $this;
    }

    public function attach(string $path, array $options = []): static
    {
        $this->attachments[] = array_merge(['path' => $path], $options);
        return $this;
    }

    public function html(string $html): static
    {
        $this->htmlBody = $html;
        return $this;
    }

    public function text(string $text): static
    {
        $this->textBody = $text;
        return $this;
    }

    public function view(string $template, array $data = []): static
    {
        $this->view     = $template;
        $this->viewData = $data;
        return $this;
    }

    public function with(string|array $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }
        return $this;
    }

    public function getSubject(): string { return $this->subject; }
    public function getFromAddress(): string { return $this->fromAddress ?: (string) \HexaGen\Core\Config::get('mail.from.address', ''); }
    public function getFromName(): string { return $this->fromName ?: (string) \HexaGen\Core\Config::get('mail.from.name', ''); }
    public function getToAddresses(): array { return $this->toAddresses; }
    public function getCcAddresses(): array { return $this->ccAddresses; }
    public function getBccAddresses(): array { return $this->bccAddresses; }
    public function getReplyToAddresses(): array { return $this->replyToAddresses; }
    public function getAttachments(): array { return $this->attachments; }

    public function getHtmlBody(): string
    {
        if ($this->view) {
            $kernel = \HexaGen\Core\Kernel::getInstance();
            if ($kernel) {
                $engine = $kernel->getContainer()->get(\HexaGen\Core\Template\TemplateEngine::class);
            } else {
                $engine = new \HexaGen\Core\Template\TemplateEngine();
            }
            return $engine->render($this->view, $this->viewData);
        }
        return $this->htmlBody;
    }

    public function getTextBody(): string { return $this->textBody; }
}
