# Installation Guide

This guide provides detailed installation instructions for the C-to-PHP FFI Converter.

## System Requirements

### Minimum Requirements

- **PHP**: 8.1 or higher
- **Extensions**: FFI extension enabled
- **Composer**: Latest stable version
- **Memory**: 128MB minimum (512MB recommended for large projects)
- **Disk Space**: 50MB for installation + space for generated files

### Recommended Requirements

- **PHP**: 8.2 or higher
- **Memory**: 1GB or more for large C projects
- **C Compiler**: gcc, clang, or MSVC (for compiling test libraries)
- **Docker**: For containerized testing (optional)

## Pre-installation Setup

### Enable PHP FFI Extension

The FFI extension is required for the generated wrappers to work.

#### Linux/macOS

1. Check if FFI is already enabled:
```bash
php -m | grep -i ffi
```

2. If not enabled, edit your `php.ini`:
```ini
extension=ffi
ffi.enable=true
```

3. Restart your web server/PHP-FPM if applicable.

#### Windows

1. Uncomment or add in `php.ini`:
```ini
extension=ffi
ffi.enable=On
```

2. Restart your web server if applicable.

#### Verify FFI Installation

```bash
php -r "if (extension_loaded('ffi')) echo 'FFI is enabled'; else echo 'FFI is not enabled';"
```

## Installation Methods

### Method 1: Global Installation (Recommended)

Install the tool globally to use it from anywhere:

```bash
composer global require yangweijie/c-to-php-ffi-converter
```

#### Add to PATH

Make sure Composer's global bin directory is in your PATH:

**Linux/macOS:**
```bash
echo 'export PATH="$PATH:$HOME/.composer/vendor/bin"' >> ~/.bashrc
source ~/.bashrc
```

**Windows:**
Add `%APPDATA%\Composer\vendor\bin` to your PATH environment variable.

#### Verify Installation

```bash
c-to-php-ffi --version
```

### Method 2: Project-specific Installation

Install as a development dependency in your project:

```bash
composer require --dev yangweijie/c-to-php-ffi-converter
```

#### Usage in Project

```bash
./vendor/bin/c-to-php-ffi --version
```

### Method 3: Manual Installation from Source

For development or custom builds:

1. **Clone the repository:**
```bash
git clone https://github.com/yangweijie/c-to-php-ffi-converter.git
cd c-to-php-ffi-converter
```

2. **Install dependencies:**
```bash
composer install --no-dev --optimize-autoloader
```

3. **Make executable:**
```bash
chmod +x bin/c-to-php-ffi
```

4. **Create symlink (optional):**
```bash
sudo ln -s $(pwd)/bin/c-to-php-ffi /usr/local/bin/c-to-php-ffi
```

## Platform-specific Instructions

### Ubuntu/Debian

```bash
# Install PHP and required extensions
sudo apt update
sudo apt install php8.1-cli php8.1-ffi composer

# Install the tool
composer global require yangweijie/c-to-php-ffi-converter

# Add to PATH
echo 'export PATH="$PATH:$HOME/.composer/vendor/bin"' >> ~/.bashrc
source ~/.bashrc
```

### CentOS/RHEL/Fedora

```bash
# Install PHP and Composer
sudo dnf install php-cli php-ffi composer

# Install the tool
composer global require yangweijie/c-to-php-ffi-converter

# Add to PATH
echo 'export PATH="$PATH:$HOME/.composer/vendor/bin"' >> ~/.bashrc
source ~/.bashrc
```

### macOS

#### Using Homebrew

```bash
# Install PHP with FFI
brew install php

# Install Composer
brew install composer

# Install the tool
composer global require yangweijie/c-to-php-ffi-converter

# Add to PATH (if not already)
echo 'export PATH="$PATH:$HOME/.composer/vendor/bin"' >> ~/.zshrc
source ~/.zshrc
```

### Windows

#### Using Chocolatey

```powershell
# Install PHP and Composer
choco install php composer

# Enable FFI in php.ini
# Edit C:\tools\php81\php.ini and add:
# extension=ffi
# ffi.enable=On

# Install the tool
composer global require yangweijie/c-to-php-ffi-converter

# Add to PATH
# Add %APPDATA%\Composer\vendor\bin to your PATH environment variable
```

#### Manual Windows Installation

1. **Download PHP** from https://windows.php.net/download/
2. **Install Composer** from https://getcomposer.org/download/
3. **Enable FFI** in php.ini
4. **Install the tool** via Composer

## Docker Installation

For containerized usage:

### Using Pre-built Image (when available)

```bash
docker pull yangweijie/c-to-php-ffi-converter:latest
docker run --rm -v $(pwd):/workspace yangweijie/c-to-php-ffi-converter generate /workspace/header.h
```

### Building from Source

```bash
git clone https://github.com/yangweijie/c-to-php-ffi-converter.git
cd c-to-php-ffi-converter
docker build -t c-to-php-ffi-converter .
docker run --rm -v $(pwd):/workspace c-to-php-ffi-converter generate /workspace/header.h
```

## Verification

### Test Installation

```bash
# Check version
c-to-php-ffi --version

# Show help
c-to-php-ffi --help

# Test with sample (if available)
c-to-php-ffi generate tests/Fixtures/sample.h --output /tmp/test
```

### Run Self-tests

If you installed from source:

```bash
composer test
```

## Troubleshooting

### Common Issues

#### "Command not found"

**Problem**: `c-to-php-ffi: command not found`

**Solutions**:
1. Ensure Composer's bin directory is in PATH
2. Use full path: `~/.composer/vendor/bin/c-to-php-ffi`
3. Reinstall globally: `composer global require yangweijie/c-to-php-ffi-converter`

#### "FFI extension not loaded"

**Problem**: `Fatal error: Uncaught Error: Class 'FFI' not found`

**Solutions**:
1. Install FFI extension: `sudo apt install php-ffi`
2. Enable in php.ini: `extension=ffi`
3. Restart web server/PHP-FPM

#### "Composer not found"

**Problem**: `composer: command not found`

**Solutions**:
1. Install Composer: https://getcomposer.org/download/
2. Use full path: `/usr/local/bin/composer`
3. Add Composer to PATH

#### Permission Issues

**Problem**: Permission denied errors

**Solutions**:
1. Check file permissions: `ls -la bin/c-to-php-ffi`
2. Make executable: `chmod +x bin/c-to-php-ffi`
3. Run with sudo if needed (not recommended for global install)

### Getting Help

If you encounter issues:

1. **Check system requirements** above
2. **Search existing issues**: https://github.com/yangweijie/c-to-php-ffi-converter/issues
3. **Create new issue** with:
   - Operating system and version
   - PHP version (`php --version`)
   - Installation method used
   - Complete error message
   - Steps to reproduce

## Updating

### Global Installation

```bash
composer global update yangweijie/c-to-php-ffi-converter
```

### Project Installation

```bash
composer update yangweijie/c-to-php-ffi-converter
```

### From Source

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
```

## Uninstallation

### Global Installation

```bash
composer global remove yangweijie/c-to-php-ffi-converter
```

### Project Installation

```bash
composer remove yangweijie/c-to-php-ffi-converter
```

### Manual Installation

```bash
rm -rf /path/to/c-to-php-ffi-converter
sudo rm /usr/local/bin/c-to-php-ffi  # if symlinked
```

---

For more information, see the [README.md](README.md) file.