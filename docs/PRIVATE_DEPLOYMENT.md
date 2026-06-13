# Private Family Deployment

Opportunity Notebook is intended for a private shared-family deployment for David Garcia and his father only.
It is one shared career-planning and opportunity-review notebook, not a public SaaS application.

## Not safe for public SaaS use

This deployment assumes that the approved users intentionally share all application data. The app does not create separate workspaces, teams, or per-user ownership scopes for domain records.

Do not use this configuration as a public multi-tenant SaaS app without adding proper authorization, tenant isolation, invitation flows, operational monitoring, and abuse protections.

## Required environment settings

Set these values before deploying to production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
OPPORTUNITY_ALLOWED_EMAILS=david@example.com,dad@example.com
```

`OPPORTUNITY_ALLOWED_EMAILS` is a comma-separated allowlist for the only accounts permitted to access authenticated app routes.

## HTTPS requirement

Serve the production app only over HTTPS. Login sessions and opportunity data should not be sent over plaintext HTTP.

## Database backups

Schedule regular database backups and periodically test restore procedures. This notebook contains career-planning history, contacts, applications, and opportunity review data that may be difficult to reconstruct manually.

## Manually creating David and Dad users

Public registration is disabled. Create the two approved accounts manually on the production server, for example with Laravel Tinker:

```bash
php artisan tinker
```

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'David Garcia',
    'email' => 'david@example.com',
    'password' => Hash::make('replace-with-a-strong-temporary-password'),
    'email_verified_at' => now(),
]);

User::create([
    'name' => 'Dad',
    'email' => 'dad@example.com',
    'password' => Hash::make('replace-with-a-strong-temporary-password'),
    'email_verified_at' => now(),
]);
```

After creating users, ensure both email addresses exactly match `OPPORTUNITY_ALLOWED_EMAILS` and have each person change their password through the profile page.

## Shared data reminder

All opportunities, contacts, actions, applications, projects, reviews, themes, and related records are shared between approved users. David and his dad should treat the notebook as a shared family workspace by design.
