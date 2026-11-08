# Private Chat Setup Guide

This guide covers local development and the credentials needed for production features.

## 1. Local email/password website

Open two PowerShell terminals.

Terminal 1:

```powershell
cd H:\laragon\www\Laravel
php artisan config:clear
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal 2:

```powershell
$env:TEMP='H:\FlutterTemp'
$env:TMP='H:\FlutterTemp'
cd H:\laragon\www\Flutter
flutter pub get
flutter run -d chrome
```

Open the Chrome URL printed by Flutter. For the Android emulator use `flutter run` and keep the API base URL on `10.0.2.2:8000`.

## 2. Authentication mode

In Laravel `.env`:

```env
AUTH_MODE=email
```

Use `AUTH_MODE=phone` for the phone/OTP screen. Then clear config and restart Laravel:

```powershell
php artisan config:clear
php artisan serve --host=127.0.0.1 --port=8000
```

For local OTP testing only:

```env
CHAT_OTP_RETURN_CODE=true
OTP_PROVIDER=log
```

The API returns `dev_otp`. Never enable this in production.

## 3. Real SMS OTP with Twilio

1. Create an account at https://www.twilio.com/try-twilio.
2. In the Twilio Console, create a Verify Service.
3. Copy the Account SID, Auth Token, and Verify Service SID into `.env`:

```env
OTP_PROVIDER=twilio
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_VERIFY_SERVICE_SID=VAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_FROM_NUMBER=
```

The current local OTP endpoint is ready for the provider integration; do not expose development OTP codes in production.

## 4. Realtime messaging with Laravel Reverb

Install and initialize Reverb:

```powershell
cd H:\laragon\www\Laravel
composer require laravel/reverb
php artisan install:broadcasting --reverb
```

Fill these values in `.env`:

```env
CHAT_REALTIME_ENABLED=true
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your_reverb_app_id
REVERB_APP_KEY=your_reverb_app_key
REVERB_APP_SECRET=your_reverb_app_secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

Run Reverb locally:

```powershell
php artisan reverb:start
```

Production should use HTTPS/WSS, a reverse proxy, and a process supervisor.

## 5. Firebase push notifications

1. Open https://console.firebase.google.com/ and create a Firebase project.
2. Add an Android app using the package id from the Flutter project.
3. Download `google-services.json` into `H:\laragon\www\Flutter\android\app\`.
4. Install Firebase CLI and FlutterFire CLI:

```powershell
npm install -g firebase-tools
firebase login
dart pub global activate flutterfire_cli
cd H:\laragon\www\Flutter
flutterfire configure
flutter pub add firebase_core firebase_messaging flutter_local_notifications
```

5. Create a Firebase service account in Project settings > Service accounts and store the JSON outside source control, for example:

```text
H:\laragon\www\Laravel\storage\app\firebase\service-account.json
```

6. Fill Laravel `.env`:

```env
CHAT_PUSH_ENABLED=true
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
FIREBASE_STORAGE_BUCKET=your-project-id.firebasestorage.app
FIREBASE_WEB_API_KEY=your_web_api_key
FIREBASE_MESSAGING_SENDER_ID=your_sender_id
FIREBASE_APP_ID=your_app_id
```

Never commit `google-services.json`, the service-account JSON, or private Firebase keys.

## 6. Attachments

For local development, keep:

```env
FILESYSTEM_DISK=local
CHAT_MAX_UPLOAD_MB=25
```

For production object storage, create an S3-compatible bucket and fill the existing `AWS_*` values in `.env`, then set `FILESYSTEM_DISK=s3`.

## 7. End-to-end encryption

Do not set `CHAT_ENCRYPTION_ENABLED=true` yet. True E2E encryption requires client-generated identity keys, per-conversation keys, secure device storage, key rotation, multi-device recovery, and a tested protocol. A Laravel `.env` secret cannot provide E2E encryption; the server must never receive plaintext keys.

Current safe defaults:

```env
CHAT_ENCRYPTION_ENABLED=false
CHAT_ENCRYPTION_MODE=client-side
CHAT_ENCRYPTION_KEY_BACKUP=false
```

## 8. Required production checklist

- Use HTTPS and WSS.
- Disable `APP_DEBUG`.
- Set `CHAT_OTP_RETURN_CODE=false`.
- Configure a real SMS provider.
- Configure Firebase and secure service-account storage.
- Configure Reverb behind a process supervisor.
- Configure object storage for attachments.
- Add rate limiting, moderation, block/report workflows, and security tests.
- Complete and audit the E2E protocol before enabling encryption.

## 9. Live database migration and user seed

After the backend deployment is updated, run these commands from Laravel Cloud's production shell:

```bash
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\ChatUsersSeeder --force
```

Before seeding, add these environment variables in Laravel Cloud. Use a strong production password instead of the local test password:

```env
CHAT_SEED_USERS=true
CHAT_SEED_PASSWORD=your_strong_password
CHAT_SEED_MOHIT_EMAIL=mohit@gmail.com
CHAT_SEED_MOHIT2_EMAIL=mohit2@gmail.com
```

After the seed completes, set `CHAT_SEED_USERS=false` and redeploy. The seeder is idempotent and will not create duplicate users.
