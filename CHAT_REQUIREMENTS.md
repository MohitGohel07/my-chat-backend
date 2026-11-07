# Private Chat Baseline

## Implemented

- Laravel API project
- Sanctum bearer-token authentication contract
- Register, login, logout, and current-user endpoints
- Private one-to-one conversations
- Participant access checks
- Cursor-paginated messages
- Text message creation
- Flutter registration, login, user search, conversation creation, message history, and message sending
- Dark Material 3 Flutter UI with environment-driven email/password or phone/OTP authentication
- Async lifecycle guards preventing `setState()` after a chat screen is disposed
- Personal and group conversation creation
- Message timestamps, read markers, and participant last-seen timestamps
- MySQL/SQLite-compatible migrations

## API

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/request-otp`
- `POST /api/auth/verify-otp`
- `GET /api/me`
- `GET /api/config`
- `GET /api/users?search=...`
- `GET /api/conversations`
- `POST /api/conversations`
- `GET /api/conversations/{conversation}`
- `GET /api/conversations/{conversation}/messages`
- `POST /api/conversations/{conversation}/messages`

## Required local setup

- PHP 8.3+
- Composer
- MySQL or SQLite
- `composer install`
- `php artisan migrate`
- Configure `DB_*` values in `.env`
- Install Laravel Sanctum if Composer did not update the lock file: `composer require laravel/sanctum`

## Configuration

- `AUTH_MODE=email` enables email/password login.
- `AUTH_MODE=phone` enables phone/OTP screens.
- `CHAT_OTP_RETURN_CODE=true` is local-development only; it returns the OTP in the API response.
- Real SMS delivery requires an SMS provider and credentials. Do not enable development OTP responses in production.

## Production work requiring credentials and design decisions

- Realtime transport: Laravel Reverb or another WebSocket provider
- Registration method: email/password or phone/OTP
- Attachments, voice messages, reactions, replies, and message editing
- Read receipts, typing indicators, and online presence
- Push notifications and Firebase credentials
- Blocking/reporting and moderation rules
- End-to-end encryption requirements
