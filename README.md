# HLS Video Streaming Platform

A comprehensive Laravel 12-based video streaming platform that combines Video-on-Demand (VOD) with resumable chunked uploads, adaptive HLS transcoding via FFmpeg, and a complete Live Streaming module with OBS Studio integration. The platform serves as a full-featured YouTube-like management dashboard where authenticated users can upload large video files (up to 10GB) through a drag-and-drop resumable chunked uploader that splits files into 5MB chunks with real-time progress tracking and integrity verification. Once uploaded, videos are automatically processed in the background using Laravel Queues and FFmpeg, which extracts a thumbnail, probes the video duration, and transcodes the video into three streaming qualities (360p at 640×360, 720p at 1280×720, and 1080p at 1920×1080) using HLS (HTTP Live Streaming) format, generating segment files (.ts) and variant playlists (.m3u8) alongside a master playlist that enables adaptive bitrate streaming. The video player, built with hls.js, provides automatic quality switching based on network conditions and a manual quality selector overlay, delivering a YouTube-like viewing experience. The platform also includes a complete Live Streaming management system where users can create, schedule, and manage live streams, with each stream generating a unique 32-character RTMP stream key and displaying the RTMP ingest URL ready for OBS Studio configuration. The live stream module features a dedicated OBS Studio configuration page with one-click copy of server URL and stream key, step-by-step setup instructions, and an HLS live player using hls.js with real-time viewer count, auto-refresh polling, and automatic stream status detection via background jobs that probe the HLS endpoint at 30-second intervals. A secure webhook receiver endpoint allows the streaming server (MediaMTX or Nginx RTMP) to notify Laravel of publish start, publish end, and viewer count update events, enabling automatic status transitions from scheduled to live to ended. The dashboard provides at-a-glance statistics for both VOD content (total videos, ready, processing, storage usage) and live streams (total streams, currently live count), with quick-action links for uploading videos, managing the library, and creating new live streams. The architecture is modular, production-ready, and designed for Ubuntu server deployment with Nginx, PHP-FPM, MySQL, and FFmpeg, while the streaming server (MediaMTX recommended for simplicity, or Nginx RTMP module) is configured separately to handle RTMP ingest and HLS delivery.

---

## Ubuntu Server Deployment

Complete step-by-step guide to deploy this platform on a fresh Ubuntu server (22.04 LTS or 24.04 LTS).

### 1. Connect to Your Server

```bash
ssh root@your-server-ip
```

### 2. Update System

```bash
apt update && apt upgrade -y
```

### 3. Install Required Packages

```bash
# Nginx web server
apt install nginx -y

# PHP 8.2 and extensions
apt install php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-redis php8.2-intl -y

# MySQL database
apt install mysql-server -y

# FFmpeg for video processing
apt install ffmpeg -y

# Composer (PHP package manager)
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Node.js and npm (for frontend assets)
apt install nodejs npm -y

# Redis (optional - for better queue performance)
apt install redis-server -y

# Supervisor (for running queue worker as a service)
apt install supervisor -y

# Git
apt install git -y
```

### 4. Clone the Repository

```bash
cd /var/www
git clone https://github.com/Muzammil2003-developer/hsl_video.git
cd hsl_video
```

### 5. Set Proper Permissions

```bash
chown -R www-data:www-data /var/www/hsl_video
chmod -R 755 /var/www/hsl_video/storage
chmod -R 755 /var/www/hsl_video/bootstrap/cache
```

### 6. Install PHP & Node Dependencies

```bash
# Install PHP packages
composer install --no-dev --optimize-autoloader

# Install and build frontend assets
npm install && npm run build
```

### 7. Configure Environment

```bash
cp .env.example .env
# Edit .env with your server details:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=hsl_video
# DB_USERNAME=root
# DB_PASSWORD=your_secure_password
# APP_URL=http://your-server-ip
```

### 8. Setup MySQL Database

```bash
mysql -u root -p

# Run these SQL commands:
CREATE DATABASE hsl_video CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hsl_user'@'localhost' IDENTIFIED BY 'your_strong_password_here';
GRANT ALL PRIVILEGES ON hsl_video.* TO 'hsl_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Now update `.env` with these credentials:
```
DB_USERNAME=hsl_user
DB_PASSWORD=your_strong_password_here
```

### 9. Generate App Key & Run Migrations

```bash
php artisan key:generate
php artisan storage:link
php artisan migrate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 10. Configure Nginx

Create a new Nginx configuration file:

```bash
nano /etc/nginx/sites-available/hsl_video
```

Paste the following configuration:

```nginx
server {
    listen 80;
    server_name your-server-ip;
    root /var/www/hsl_video/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Increase upload size for large video files
    client_max_body_size 10G;

    # Increase PHP execution time for large uploads
    fastcgi_read_timeout 3600;
    proxy_read_timeout 3600;
}
```

Enable the site and restart Nginx:

```bash
ln -s /etc/nginx/sites-available/hsl_video /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
```

### 11. Setup Queue Worker with Supervisor

Create a Supervisor configuration file:

```bash
nano /etc/supervisor/conf.d/hsl-worker.conf
```

Paste:

```ini
[program:hsl-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/hsl_video/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/hsl_video/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start all
```

### 12. Setup Cron Job for Task Scheduling

```bash
crontab -e
```

Add this line:

```bash
* * * * * cd /var/www/hsl_video && php artisan schedule:run >> /dev/null 2>&1
```

### 13. Set File Permissions (Final)

```bash
chown -R www-data:www-data /var/www/hsl_video/storage
chown -R www-data:www-data /var/www/hsl_video/bootstrap/cache
chmod -R 775 /var/www/hsl_video/storage
chmod -R 775 /var/www/hsl_video/bootstrap/cache
```

### 14. (Optional) Set Up Streaming Server - MediaMTX

For live streaming functionality, install MediaMTX:

```bash
cd /opt
wget https://github.com/bluenviron/mediamtx/releases/latest/download/mediamtx_linux_amd64.tar.gz
tar -xzf mediamtx_linux_amd64.tar.gz
chmod +x mediamtx
```

Edit MediaMTX config:

```bash
nano mediamtx.yml
```

Enable webhook notifications (add these lines):
```yaml
webhooks:
  actions: ["publish", "publish_done"]
  url: http://127.0.0.1/streaming/webhook
```

Run MediaMTX as a service:

```bash
cat << EOF > /etc/systemd/system/mediamtx.service
[Unit]
Description=MediaMTX
After=network.target

[Service]
Type=simple
WorkingDirectory=/opt
ExecStart=/opt/mediamtx
Restart=always
User=root

[Install]
WantedBy=multi-user.target
EOF

systemctl enable mediamtx
systemctl start mediamtx
```

Update your Laravel `.env`:
```
RTMP_SERVER=rtmp://your-server-ip:1935
HLS_BASE_URL=http://your-server-ip:8888
STREAMING_WEBHOOK_SECRET=your_random_secret_here
```

### 15. (Optional) Secure with SSL (Let's Encrypt)

```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d your-domain.com
```

### 16. Final Steps

```bash
# Verify everything is running
systemctl status nginx
systemctl status php8.2-fpm
systemctl status mysql
supervisorctl status
systemctl status mediamtx  # if installed

# Your site should be live at: http://your-server-ip
```

---

## Features

### Video-on-Demand (VOD)
- **Resumable Chunked Uploads** — Drag-and-drop interface, 5MB chunks, 10GB max file size
- **Real-time Upload Progress** — Progress bar with chunk details, cancel/reset/retry
- **FFmpeg Background Processing** — Queue-based transcoding with Laravel Queues
- **Adaptive HLS Streaming** — 360p, 720p, 1080p with automatic ABR and manual quality switching
- **Video Management** — Grid view with thumbnails, status badges, edit/delete
- **Thumbnail Generation** — Auto-extracted at 10% into video

### Live Streaming
- **Stream Management** — Create, schedule, start, end, and delete live streams
- **OBS Studio Integration** — RTMP URL + stream key ready for OBS configuration
- **Unique Stream Keys** — 32-character random keys per stream, regeneratable
- **HLS Live Player** — Real-time playback via hls.js with viewer count overlay
- **Auto Status Detection** — Background job polls HLS endpoint to detect go-live/end events
- **Webhook Receiver** — Secure endpoint for streaming server event notifications
- **Stream Scheduling** — Set future dates for scheduled streams with countdown
- **Viewer Analytics** — Real-time viewer count, peak viewer tracking

## Quick Start (Local Development)

```bash
# Requirements: PHP 8.2+, MySQL, FFmpeg, Composer, Node.js

composer install
npm install && npm run build
cp .env.example .env
# Edit .env: DB_CONNECTION=mysql, DB_DATABASE=hsl_video, DB_USERNAME=root, DB_PASSWORD=
php artisan key:generate
php artisan storage:link
php artisan migrate

# Terminal 1 - Web Server
php artisan serve

# Terminal 2 - Queue Worker (for video processing)
php artisan queue:work
```

Visit `http://localhost:8000`, register an account, and start uploading videos or creating live streams.

## Routes

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/videos` | Video library |
| GET | `/videos/upload` | Upload page |
| POST | `/upload/initiate` | Start chunked upload |
| POST | `/upload/chunk` | Upload chunk |
| POST | `/upload/finalize` | Assemble chunks |
| GET | `/videos/{id}` | Watch video (HLS) |
| GET | `/videos/{id}/master.m3u8` | HLS master playlist |
| GET | `/live` | Live streams list |
| GET | `/live/create` | Create live stream |
| POST | `/live` | Store live stream |
| POST | `/live/{id}/start` | Go live |
| POST | `/live/{id}/stop` | End stream |
| GET | `/live/{id}/obs-config` | OBS configuration page |
| POST | `/streaming/webhook` | Streaming server webhook |

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+, MySQL
- **Video Processing**: FFmpeg, Laravel Queues
- **Streaming**: HLS (HTTP Live Streaming), RTMP
- **Frontend**: Blade, Tailwind CSS, hls.js
- **Auth**: Laravel Breeze