# CLI Reference

All commands run via `php hexaphp <command>`.

## Generators

| Command | Description |
|---|---|
| `make:slice Name` | Full vertical slice (controller, model, routes, services) |
| `make:model Name` | Model class |
| `make:controller Name` | Controller class |
| `make:request Name` | FormRequest class |
| `make:migration name` | Migration file |
| `make:seeder Name` | Seeder class |
| `make:factory Name` | Model factory |
| `make:mail Name` | Mailable class |
| `make:job Name` | Queue job |
| `make:event Name` | Event class |
| `make:middleware Name` | Middleware class |
| `make:provider Name` | Service provider |
| `make:notification Name` | Notification class |
| `make:resource Name` | API resource |
| `make:test Name` | Test class |

## Database

| Command | Description |
|---|---|
| `migrate` | Run pending migrations |
| `migrate:rollback` | Rollback last batch |
| `migrate:rollback --step=N` | Rollback last N batches |
| `migrate:fresh` | Drop all tables + re-run migrations |
| `migrate:fresh --seed` | + run seeders |
| `migrate:status` | Show ran / pending migrations |
| `db:seed` | Run DatabaseSeeder |
| `db:seed --class=Name` | Run a specific seeder |

## Queue

| Command | Description |
|---|---|
| `queue:install` | Create jobs table migration |
| `queue:work` | Start queue worker |
| `queue:work --queue=name` | Work a specific queue |
| `queue:failed` | List failed jobs |

## Scheduler

| Command | Description |
|---|---|
| `schedule:list` | Show all scheduled tasks |
| `schedule:run` | Run due tasks (for cron: `* * * * * php hexaphp schedule:run`) |
| `schedule:work` | Daemon — runs tasks as they come due, graceful SIGTERM shutdown |

## Cache & Optimization

| Command | Description |
|---|---|
| `config:cache` | Compile all config files into one cached file |
| `config:clear` | Remove config cache |
| `optimize` | Cache config + routes |
| `optimize:clear` | Clear all caches |

## OpenAPI

| Command | Description |
|---|---|
| `openapi:generate` | Generate `openapi.json` from your route definitions |

## Maintenance

| Command | Description |
|---|---|
| `down` | Put app in maintenance mode (503 for all requests) |
| `up` | Bring app back online |

## Development

| Command | Description |
|---|---|
| `server:start` | Start FrankenPHP worker server (auto-downloads if needed) |
| `tinker` | Interactive REPL (PsySH) with framework bootstrap |

## Cron setup for scheduler

Add this single cron entry on your server:

```bash
* * * * * cd /path/to/my-app && php hexaphp schedule:run >> /dev/null 2>&1
```

Or use the daemon (recommended with supervisor):

```bash
php hexaphp schedule:work
```
