# HTTPS Setup with ngrok

This guide explains how to set up HTTPS for local development using ngrok.

## Prerequisites

1. Laravel development server running on `http://localhost:8000`
2. Free ngrok account

## Setup Steps

### 1. Create ngrok Account

1. Go to https://ngrok.com and create a free account
2. After logging in, go to "Your Authtoken" in the dashboard
3. Copy your authtoken

### 2. Install ngrok

**Option A: Download manually**
- Download from https://ngrok.com/download
- Extract to a folder in your PATH (e.g., `C:\ngrok\`)

**Option B: Using Chocolatey (Windows)**
```powershell
choco install ngrok
```

**Option C: Using winget (Windows)**
```powershell
winget install ngrok
```

### 3. Configure ngrok

Open a terminal and run:
```powershell
ngrok config add-authtoken YOUR_AUTH_TOKEN_HERE
```

### 4. Start Your Laravel Server

```powershell
cd C:\Users\jarma\Documents\SubWFour
php artisan serve
```

### 5. Start ngrok Tunnel

In a **new terminal**, run:
```powershell
ngrok http 8000
```

You'll see output like:
```
Session Status                online
Forwarding                    https://abc123xyz.ngrok-free.app -> http://localhost:8000
```

### 6. Update .env

Update your `.env` file with the ngrok URL:
```env
APP_URL=https://abc123xyz.ngrok-free.app
```

**Note:** The ngrok URL changes each time you restart ngrok (unless you have a paid plan with custom domains).

## Using HTTPS

After setup, your application will be accessible at:
- `https://your-subdomain.ngrok-free.app`

This provides:
- Valid SSL/TLS certificate
- HTTPS encryption
- Publicly accessible URL (useful for testing webhooks, mobile apps, etc.)

## Session Configuration for HTTPS

The application is already configured for secure sessions. When using HTTPS via ngrok, update `.env`:

```env
SESSION_SECURE_COOKIE=true
```

## Important Notes

1. **Free tier limitations**: 
   - URL changes each restart
   - Limited connections
   - ngrok branding page on first visit

2. **For production**: Use a proper SSL certificate with your domain, not ngrok.

3. **Resend emails**: Make sure your Resend "From" address uses a verified domain.

## Troubleshooting

### "Invalid Host header" error
Add to ngrok command:
```powershell
ngrok http 8000 --host-header=localhost
```

### Session issues
Clear sessions after changing APP_URL:
```powershell
php artisan cache:clear
php artisan config:clear
```
