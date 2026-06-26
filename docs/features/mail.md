# Mail

## Creating a Mailable

```bash
php hexaphp make:mail WelcomeMail
```

```php
use HexaGen\Core\Mail\Mailable;

class WelcomeMail extends Mailable
{
    public function __construct(private User $user) {}

    public function build(): static
    {
        return $this
            ->subject('Welcome to ' . config('app.name'))
            ->view('emails.welcome')
            ->with(['user' => $this->user]);
    }
}
```

## Sending mail

```php
Mail::to('user@example.com')->send(new WelcomeMail($user));
Mail::to($user)->send(new WelcomeMail($user));
Mail::to([$user1, $user2])->cc('manager@example.com')->send(new ReportMail());
```

## Drivers

| Driver | Use case |
|---|---|
| `smtp` | Production sending |
| `log` | Logs email to file — no actual send |
| `null` | Discards silently — for testing |

```ini
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your@domain.com
MAIL_PASSWORD=secret
MAIL_FROM=noreply@my-app.com
MAIL_FROM_NAME="My App"
```
