# 📋 NID Verification Site

<div align="center">

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg?cacheSeconds=2592000)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](#)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-blue.svg)](https://www.mysql.com)

A robust web application for verifying National Identity Documents (NID) with secure handling and real-time status updates.

[📘 Documentation](howto/) • [🐛 Report Bug](../../issues) • [📝 Request Feature](../../issues)

</div>

---

## 📚 Table of Contents

- [🚀 Features](#-features)
- [⚡ Tech Stack](#-tech-stack)
- [📋 Requirements](#-requirements)
- [🔧 Installation](#-installation)
- [⚙️ Configuration](#️-configuration)
- [👥 User Guide](#-user-guide)
- [👨‍💼 Admin Guide](#-admin-guide)
- [❗ Common Issues](#-common-issues)
- [🤝 Contributing](#-contributing)
- [📝 License](#-license)
- [👨‍💻 Author](#-author)

## 🚀 Features

- **Secure Document Verification**
  - NID validation and verification
  - Multi-document support (NID, Passport, Birth Certificate)
  - Real-time status tracking

- **User-Friendly Interface**
  - Intuitive submission process
  - Mobile-responsive design
  - Clear status updates

- **Robust Admin Panel**
  - Document review system
  - User management
  - Audit logging
  - Email notifications

- **Security Features**
  - Secure file handling
  - Data encryption
  - Access control
  - Activity logging

## ⚡ Tech Stack

- **Backend**
  - ![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
  - ![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-blue)
  - ![Apache](https://img.shields.io/badge/Apache/Nginx-Server-green)

- **Frontend**
  - ![HTML5](https://img.shields.io/badge/HTML5-markup-orange)
  - ![CSS3](https://img.shields.io/badge/CSS3-styles-blue)
  - ![JavaScript](https://img.shields.io/badge/JavaScript-ES6-yellow)

- **Libraries**
  - ![PHPMailer](https://img.shields.io/badge/PHPMailer-SMTP-red)

## 📋 Requirements

### System Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- SSL certificate (recommended)
- Minimum 1GB RAM
- At least 10GB storage

### PHP Extensions
- mysqli
- gd
- fileinfo
- json
- openssl

## 🔧 Installation

### 1️⃣ Local Development Setup

```bash
# Clone the repository
git clone https://github.com/RaiyanRafid/NID-Verification-Site.git
cd NID-Verification-Site

# Install dependencies
composer install

# Set up database
mysql -u root -p
CREATE DATABASE nid_verification;
exit;
```

### 2️⃣ Production Deployment

#### Shared Hosting
1. Upload files to `public_html` via FTP
2. Set file permissions:
   ```bash
   chmod 755 directories
   chmod 644 files
   chmod 777 uploads/
   ```
3. Create MySQL database via cPanel
4. Import database structure
5. Update configuration

#### VPS Setup
```bash
# Update system
sudo apt update && sudo apt upgrade

# Install requirements
sudo apt install apache2 mysql-server php php-mysql

# Configure SSL
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d your-domain.com
```

## ⚙️ Configuration

### Database Setup
```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database');
```

### Email Configuration
Configure in admin panel:
- SMTP Host
- SMTP Port
- SMTP Username
- SMTP Password
- From Email
- From Name

## 👥 User Guide

### Submitting Verification Request
1. Visit website homepage
2. Fill personal details:
   - First Name
   - Middle Name (optional)
   - Last Name
   - Address
   - Phone Number
   - Email Address
3. Select document type
4. Upload document images
5. Submit and save reference number

### Tracking Status
1. Visit tracking page
2. Enter reference number
3. View current status

## 👨‍💼 Admin Guide

### Admin Access
- URL: `/admin`
- Default credentials:
  ```
  Username: admin
  Password: admin123
  ```
> ⚠️ **Warning:** Change default credentials immediately!

### Management Features
- View pending requests
- Review documents
- Verify/reject requests
- Add comments
- Track history
- Configure system settings

## ❗ Common Issues

### 🔴 Database Connection
- Check credentials
- Verify MySQL service
- Check permissions

### 🔴 Upload Problems
- Directory permissions
- PHP upload limits
- File restrictions

### 🔴 Email Issues
- SMTP settings
- Firewall rules
- Provider restrictions

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create your feature branch
   ```bash
   git checkout -b feature/AmazingFeature
   ```
3. Commit changes
   ```bash
   git commit -m 'Add AmazingFeature'
   ```
4. Push to branch
   ```bash
   git push origin feature/AmazingFeature
   ```
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Raiyan Rafid**

* Website: [your-website.com](https://raiyanhossain.net)
* GitHub: [@RaiyanRafid](https://github.com/RaiyanRafid)
* LinkedIn: [@raiyanrafid](https://linkedin.com/in/raiyanrafid)

---

<div align="center">

### ⭐ Star this repository if you find it helpful!

[Report Bug](../../issues) • [Request Feature](../../issues)

</div> 
