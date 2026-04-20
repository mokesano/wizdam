# Wizdam AI-Sikola - Panduan Instalasi dan Penggunaan

## Persyaratan Sistem

- **PHP**: Versi 8.1 atau lebih baru
- **Database**: MariaDB 10.6+ atau MySQL 8.0+
- **Web Server**: Apache dengan mod_rewrite atau Nginx
- **Extensions PHP**: PDO, JSON, MBString, cURL, Redis (opsional untuk queue)
- **Composer**: Untuk manajemen dependensi
- **Node.js & npm**: Untuk kompilasi aset frontend (opsional)

## Struktur Direktori

```
/workspace
├── app/                    # Logika bisnis aplikasi
│   ├── Core/              # Komponen inti (App, Auth, Container)
│   ├── Http/              # Request, Response, Router, Middleware
│   ├── Services/          # Service layer (API Client, Auth Manager)
│   ├── Repositories/      # Data access layer
│   ├── Models/            # Entity/DTO classes
│   ├── Jobs/              # Background jobs untuk queue system
│   └── Handlers/          # Request handlers
├── library/                # Library kustom Wizdam
│   ├── Core/              # Base classes
│   ├── Database/          # Database managers
│   ├── Geo/               # GeoIP utilities
│   └── Helpers/           # Helper functions
├── config/                 # File konfigurasi
├── public/                 # Web root (document root)
│   ├── assets/            # CSS, JS, images
│   └── index.php          # Entry point aplikasi
├── views/                  # Template Twig
├── storage/                # File storage
│   ├── logs/              # Log files
│   ├── cache/             # Cache files
│   └── queue/             # Queue data
├── composer.json           # Composer configuration
├── database_schema_full.sql # Skema database lengkap
└── .env.example           # Contoh environment variables
```

## Langkah Instalasi

### 1. Clone Repository

```bash
git clone <repository-url> wizdam-sikola
cd wizdam-sikola
```

### 2. Install Dependensi PHP

```bash
composer install --no-dev --optimize-autoloader
```

**Catatan**: Karena lingkungan ini tidak memiliki Composer terinstall, Anda perlu:
- Menjalankan `composer install` di server produksi
- Atau mengupload folder `vendor/` yang sudah di-generate dari lokal

### 3. Install Dependensi Frontend (Opsional)

```bash
npm install
npm run build
```

### 4. Konfigurasi Environment

Salin file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

Kemudian edit file `.env` sesuai kebutuhan:

```env
# Application
APP_NAME="Wizdam AI-Sikola"
APP_ENV=development
APP_DEBUG=true
APP_URL=https://www.sangia.org
BASE_PATH=/var/www/wizdam-sikola

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=wizdam_sikola
DB_USERNAME=root
DB_PASSWORD=your_secure_password
DB_CHARSET=utf8mb4

# Redis (untuk queue - opsional)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# API Configuration
SANGIA_API_URL=https://api.sangia.org
SANGIA_API_VERSION=v1
API_TIMEOUT=30
API_ASYNC_ENABLED=true

# Security
SESSION_LIFETIME=120
ENCRYPTION_KEY=your_32_character_random_key

# File Upload
MAX_UPLOAD_SIZE=10485760
ALLOWED_EXTENSIONS=jpg,jpeg,png,pdf,doc,docx

# GeoIP
GEOIP_DB_PATH=/path/to/GeoIP.dat

# Queue Settings
QUEUE_DRIVER=database  # atau 'redis'
QUEUE_CONNECTION=default
```

### 5. Setup Database

1. Buat database baru:

```sql
CREATE DATABASE wizdam_sikola CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import skema database:

```bash
mysql -u username -p wizdam_sikola < database_schema_full.sql
```

Atau gunakan CLI MySQL:

```sql
SOURCE /path/to/database_schema_full.sql;
```

### 6. Konfigurasi Web Server

#### Apache

Pastikan mod_rewrite aktif dan buat virtual host:

```apache
<VirtualHost *:80>
    ServerName www.sangia.org
    DocumentRoot /var/www/wizdam-sikola/public
    
    <Directory /var/www/wizdam-sikola/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/wizdam-error.log
    CustomLog ${APACHE_LOG_DIR}/wizdam-access.log combined
</VirtualHost>
```

Aktifkan rewrite module:

```bash
a2enmod rewrite
systemctl restart apache2
```

#### Nginx

```nginx
server {
    listen 80;
    server_name www.sangia.org;
    root /var/www/wizdam-sikola/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 7. Set Permissions

```bash
chmod -R 755 storage/
chmod -R 755 public/assets/
chown -R www-data:www-data storage/
```

### 8. Generate Encryption Key (Opsional)

Generate kunci enkripsi aman untuk session dan data sensitif:

```php
<?php
echo bin2hex(random_bytes(16));
```

Simpan hasilnya di `.env` pada variabel `ENCRYPTION_KEY`.

## Menjalankan Aplikasi

### Development

Jika menggunakan PHP built-in server (hanya untuk development):

```bash
cd public
php -S localhost:8000
```

Akses aplikasi di: `http://localhost:8000`

### Production

Aplikasi akan otomatis berjalan ketika:
1. Web server sudah dikonfigurasi dengan benar
2. Database sudah di-setup
3. File `.env` sudah dikonfigurasi

Tidak ada perintah khusus untuk "menjalankan" aplikasi. Cukup akses domain Anda:
- Homepage: `https://www.sangia.org`
- Login: `https://www.sangia.org/login`
- Dashboard: `https://www.sangia.org/dashboard`
- API Management: `https://www.sangia.org/admin/api-keys`

## Background Jobs & Queue System

Untuk memproses job antrian (crawler, analisis impact):

### Menggunakan Database Queue

Jalankan worker process:

```bash
php app/Jobs/Worker.php
```

Atau jalankan sebagai service systemd:

```ini
# /etc/systemd/system/wizdam-worker.service
[Unit]
Description=Wizdam AI-Sikola Queue Worker
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/wizdam-sikola
ExecStart=/usr/bin/php /var/www/wizdam-sikola/app/Jobs/Worker.php
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Aktifkan service:

```bash
systemctl enable wizdam-worker
systemctl start wizdam-worker
```

### Menggunakan Redis Queue (Recommended untuk Production)

Jika menggunakan Redis, pastikan Redis berjalan dan konfigurasi di `.env`:

```env
QUEUE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Manajemen API Keys

API Keys dikelola melalui aplikasi di `www.sangia.org`:

1. Login sebagai admin
2. Navigasi ke **Admin Panel > API Keys**
3. Klik **Generate New Key**
4. Isi nama key, pilih permissions, dan tentukan expiration
5. Simpan key yang ditampilkan (hanya muncul sekali!)

API Keys disimpan di database tabel `api_keys` dan divalidasi di `api.sangia.org`.

## Developer Portal

Dokumentasi API tersedia di `https://developers.sangia.org` yang berisi:
- Endpoint documentation
- Authentication guide
- Rate limiting info
- Code examples
- SDK downloads

## Testing

Jalankan test suite (jika tersedia):

```bash
./vendor/bin/phpunit
```

## Troubleshooting

### Error: Permission Denied

```bash
chmod -R 755 storage/
chown -R www-data:www-data storage/
```

### Error: Database Connection Failed

- Pastikan database sudah dibuat
- Cek kredensial di `.env`
- Pastikan user database memiliki akses

### Error: Class Not Found

```bash
composer dump-autoload --optimize
```

### Queue Tidak Berjalan

- Cek konfigurasi driver queue di `.env`
- Pastikan worker process berjalan
- Cek log di `storage/logs/queue.log`

### Timeout pada Analisis Besar

- Aktifkan mode async di `.env`: `API_ASYNC_ENABLED=true`
- Pastikan worker process berjalan
- Gunakan polling di frontend untuk cek status job

## Update Aplikasi

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php app/Core/Migrator.php run  # Jika ada migrasi baru
systemctl restart wizdam-worker
```

## Keamanan

- Ganti semua default password
- Gunakan HTTPS di production
- Backup database secara berkala
- Update dependencies secara rutin
- Batasi akses ke direktori `storage/` dan `config/`
- Jangan commit file `.env` ke repository

## Dukungan

Untuk pertanyaan dan dukungan, hubungi tim Wizdam atau buka issue di repository.

---

**Wizdam AI-Sikola** © 2024 - Platform Pengukuran Dampak Riset Indonesia
