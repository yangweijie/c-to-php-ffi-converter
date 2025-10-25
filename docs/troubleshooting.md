# Troubleshooting Guide

This guide covers common issues and their solutions when using the C-to-PHP FFI Converter.

## Installation Issues

### FFI Extension Not Available

**Problem**: `Fatal error: Uncaught Error: Class 'FFI' not found`

**Cause**: The FFI extension is not installed or enabled.

**Solutions**:

1. **Check if FFI is installed**:
```bash
php -m | grep -i ffi
```

2. **Install FFI extension** (if not installed):
```bash
# Ubuntu/Debian
sudo apt install php-ffi

# CentOS/RHEL
sudo yum install php-ffi

# macOS with Homebrew
brew install php --with-ffi
```

3. **Enable FFI in php.ini**:
```ini
extension=ffi
ffi.enable=true
```

4. **Restart web server** (if applicable):
```bash
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

### Composer Autoloader Not Found

**Problem**: `Could not find autoloader. Please run 'composer install'.`

**Cause**: Dependencies are not installed or autoloader is missing.

**Solutions**:

1. **Install dependencies**:
```bash
composer install
```

2. **Check installation method**:
```bash
# For global installation
composer global require yangweijie/c-to-php-ffi-converter

# For project installation
composer require --dev yangweijie/c-to-php-ffi-converter
```

3. **Verify PATH** (for global installation):
```bash
echo $PATH | grep composer
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

## Library Loading Issues

### Shared Library Not Found

**Problem**: `Cannot load library: /path/to/library.so`

**Cause**: The shared library file doesn't exist or isn't accessible.

**Solutions**:

1. **Verify library exists**:
```bash
ls -la /path/to/library.so
```

2. **Check library dependencies**:
```bash
ldd /path/to/library.so
```

3. **Set library path**:
```bash
export LD_LIBRARY_PATH=/path/to/library/directory:$LD_LIBRARY_PATH
```

4. **Use absolute paths**:
```bash
c-to-php-ffi generate header.h --library /absolute/path/to/library.so
```

### Architecture Mismatch

**Problem**: `library.so: wrong ELF class: ELFCLASS32`

**Cause**: Library architecture doesn't match PHP architecture (32-bit vs 64-bit).

**Solutions**:

1. **Check PHP architecture**:
```bash
php -r "echo PHP_INT_SIZE * 8; echo ' bit PHP';"
```

2. **Recompile library** for correct architecture:
```bash
# For 64-bit
gcc -m64 -shared -fPIC -o library.so source.c

# For 32-bit
gcc -m32 -shared -fPIC -o library.so source.c
```

## Header File Issues

### Header File Not Found

**Problem**: `Cannot read header file: /path/to/header.h`

**Cause**: Header file doesn't exist or isn't readable.

**Solutions**:

1. **Verify file exists**:
```bash
ls -la /path/to/header.h
```

2. **Check permissions**:
```bash
chmod 644 /path/to/header.h
```

3. **Use absolute paths**:
```bash
c-to-php-ffi generate /absolute/path/to/header.h
```

### Include Dependencies Missing

**Problem**: `fatal error: 'dependency.h' file not found`

**Cause**: Header file includes other headers that can't be found.

**Solutions**:

1. **Install development packages**:
```bash
# Ubuntu/Debian
sudo apt install build-essential libc6-dev

# CentOS/RHEL
sudo yum groupinstall "Development Tools"
```

2. **Add include paths** to your C compilation:
```bash
gcc -I/usr/include -I/usr/local/include -shared -fPIC -o library.so source.c
```

3. **Copy missing headers** to your project directory.

## Generation Issues

### Memory Exhaustion

**Problem**: `Fatal error: Allowed memory size exhausted`

**Cause**: Large header files or complex projects exceed PHP memory limit.

**Solutions**:

1. **Increase memory limit**:
```bash
php -d memory_limit=1G c-to-php-ffi generate header.h
```

2. **Split large headers** into smaller files.

3. **Use exclusion patterns**:
```yaml
exclude_patterns:
  - "internal_*"
  - "_private_*"
```

### Permission Denied

**Problem**: `Permission denied: cannot write to output directory`

**Cause**: Insufficient permissions to write to output directory.

**Solutions**:

1. **Check directory permissions**:
```bash
ls -la /path/to/output/directory
```

2. **Create directory with proper permissions**:
```bash
mkdir -p /path/to/output
chmod 755 /path/to/output
```

3. **Change ownership** (if needed):
```bash
sudo chown $USER:$USER /path/to/output
```

## Runtime Issues

### Validation Errors

**Problem**: `ValidationException: Parameter type mismatch`

**Cause**: Generated wrapper is validating parameters strictly.

**Solutions**:

1. **Check parameter types**:
```php
// Ensure correct types
$result = $math->add(5, 3);        // int, int ✓
$result = $math->add("5", 3);      // string, int ✗
```

2. **Disable validation** (if needed):
```yaml
validation:
  enable_parameter_validation: false
```

3. **Use type conversion**:
```yaml
validation:
  enable_type_conversion: true
```

### Segmentation Faults

**Problem**: `Segmentation fault (core dumped)`

**Cause**: Memory access violations in C library or incorrect FFI usage.

**Solutions**:

1. **Enable debugging**:
```bash
DEBUG=1 c-to-php-ffi generate header.h
```

2. **Test C library independently**:
```c
// Create test program
#include "library.h"
int main() {
    // Test functions here
    return 0;
}
```

3. **Check pointer handling**:
```php
// Ensure proper pointer usage
$ptr = $lib->createPointer();
// Use $ptr...
$lib->freePointer($ptr); // Clean up if needed
```

## Configuration Issues

### YAML Parsing Errors

**Problem**: `Unable to parse YAML configuration`

**Cause**: Invalid YAML syntax in configuration file.

**Solutions**:

1. **Validate YAML syntax**:
```bash
php -r "yaml_parse_file('config.yaml');"
```

2. **Check indentation** (use spaces, not tabs):
```yaml
header_files:
  - header1.h  # 2 spaces
  - header2.h  # 2 spaces
```

3. **Quote special characters**:
```yaml
library_file: "/path/with spaces/library.so"
```

### Configuration Not Found

**Problem**: `Configuration file not found: config.yaml`

**Cause**: Configuration file doesn't exist or path is incorrect.

**Solutions**:

1. **Use absolute path**:
```bash
c-to-php-ffi generate --config /absolute/path/to/config.yaml
```

2. **Check current directory**:
```bash
ls -la config.yaml
```

3. **Create default configuration**:
```bash
c-to-php-ffi init-config > config.yaml
```

## Performance Issues

### Slow Generation

**Problem**: Generation takes very long time.

**Cause**: Large header files or complex dependency resolution.

**Solutions**:

1. **Use exclusion patterns**:
```yaml
exclude_patterns:
  - "test_*"
  - "*_internal"
```

2. **Limit header files**:
```yaml
header_files:
  - essential_header.h
  # Comment out non-essential headers
  # - optional_header.h
```

3. **Increase memory limit**:
```bash
php -d memory_limit=2G c-to-php-ffi generate header.h
```

## Platform-Specific Issues

### Windows Issues

**Problem**: Various Windows-specific errors.

**Solutions**:

1. **Use Windows paths**:
```bash
c-to-php-ffi generate C:\path\to\header.h --library C:\path\to\library.dll
```

2. **Install Visual C++ Redistributable**.

3. **Use WSL** for Linux-like environment:
```bash
wsl --install
```

### macOS Issues

**Problem**: Library loading issues on macOS.

**Solutions**:

1. **Set DYLD_LIBRARY_PATH**:
```bash
export DYLD_LIBRARY_PATH=/path/to/library:$DYLD_LIBRARY_PATH
```

2. **Use .dylib extension**:
```bash
gcc -shared -o library.dylib source.c
```

3. **Install Xcode Command Line Tools**:
```bash
xcode-select --install
```

## Getting More Help

### Debug Information

Enable debug mode for more information:

```bash
DEBUG=1 c-to-php-ffi generate header.h --verbose
```

### Log Files

Check log files (if configured):

```bash
tail -f /tmp/c-to-php-ffi.log
```

### System Information

Gather system information for bug reports:

```bash
php --version
php -m | grep -i ffi
composer --version
uname -a
```

### Reporting Issues

When reporting issues, include:

1. **Complete error message**
2. **System information** (OS, PHP version, etc.)
3. **Steps to reproduce**
4. **Sample header file** (if possible)
5. **Configuration used**

Create an issue at: https://github.com/yangweijie/c-to-php-ffi-converter/issues

### Community Support

- **GitHub Discussions**: https://github.com/yangweijie/c-to-php-ffi-converter/discussions
- **Stack Overflow**: Tag questions with `c-to-php-ffi-converter`
- **PHP FFI Documentation**: https://www.php.net/manual/en/book.ffi.php

## FAQ

### Q: Can I use this with C++ libraries?

A: The tool is designed for C libraries. For C++, you'll need to create C wrapper functions or use extern "C" declarations.

### Q: Does this work with all C libraries?

A: Most standard C libraries work well. Complex libraries with heavy use of function pointers or callbacks may need manual adjustments.

### Q: How do I handle callbacks?

A: Callbacks require manual implementation. The tool generates basic wrappers, but callback handling needs custom code.

### Q: Can I customize the generated code?

A: Yes, you can modify the Twig templates or post-process the generated files.

### Q: Is this production-ready?

A: The tool generates production-ready wrappers, but always test thoroughly with your specific use case.