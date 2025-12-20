# Mixpost Production Deployment Guide

## Prerequisites

- PHP 8.1+
- MySQL 5.7+ or PostgreSQL
- Node.js 16+
- Composer
- Redis (recommended for queues)

## Installation

### 1. Install via Composer

```bash
composer require inovector/mixpost
```

### 2. Run Installation

```bash
php artisan mixpost:install
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Publish Assets

```bash
php artisan mixpost:publish-assets
```

## Configuration

### Environment Variables

Add to your `.env`:

```env
# Queue driver (recommended: redis)
QUEUE_CONNECTION=redis

# AI Assistant (optional)
OPENAI_API_KEY=your-key-here

# Social Platform API Keys (configure in Services page)
```

### Scheduler Setup

Add to your server's crontab:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Queue Worker

Run the queue worker:

```bash
php artisan queue:work --queue=default,publish-post
```

For production, use Supervisor:

```ini
[program:mixpost-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-project/artisan queue:work --queue=default,publish-post
autostart=true
autorestart=true
numprocs=1
user=www-data
```

## Social Platform Setup

1. Navigate to **Services** in Mixpost
2. Configure API credentials for each platform:
   - Facebook/Meta
   - Twitter/X
   - LinkedIn
   - Mastodon
   - (others as needed)

## Security

### Authentication

Mixpost uses your Laravel authentication. Ensure you have:
- User registration/login working
- Proper session configuration
- HTTPS enabled

### Permissions Gate

By default, all authenticated users can access Mixpost. To customize:

```php
// In AppServiceProvider boot()
Gate::define('viewMixpost', function ($user) {
    return $user->isAdmin(); // Your custom logic
});
```

## Upgrading

```bash
composer update inovector/mixpost
php artisan migrate
php artisan mixpost:publish-assets --force
```

## Commands Reference

| Command | Description |
|---------|-------------|
| `mixpost:run-scheduled-posts` | Publish scheduled posts |
| `mixpost:process-queue` | Process queue items |
| `mixpost:publish-assets` | Publish frontend assets |
| `mixpost:clear-settings-cache` | Clear settings cache |
| `mixpost:clear-services-cache` | Clear services cache |
