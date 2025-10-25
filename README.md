# C-to-PHP FFI Converter

A powerful PHP tool that automatically generates object-oriented PHP FFI wrapper classes from C projects. Built on top of [klitsche/ffigen](https://github.com/klitsche/ffigen), it provides enhanced functionality including parameter validation, error handling, and comprehensive documentation generation.

[中文版 README](README_ZH.md)

## Features

- **Automatic Wrapper Generation**: Convert C header files to PHP FFI wrapper classes
- **Parameter Validation**: Runtime type checking and validation for C function parameters
- **Error Handling**: Comprehensive error handling with descriptive exceptions
- **Documentation Generation**: Automatic PHPDoc comments and usage examples
- **CLI Interface**: Easy-to-use command-line interface
- **Flexible Configuration**: Support for YAML configuration files and CLI options
- **Dependency Resolution**: Automatic handling of header file dependencies

## Requirements

- PHP 8.1 or higher
- PHP FFI extension enabled
- Composer
- C compiler (for compiling example libraries)

## Installation

### Global Installation (Recommended)

Install globally via Composer:

```bash
composer global require yangweijie/c-to-php-ffi-converter
```

Make sure your global Composer bin directory is in your PATH:

```bash
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

### Project-specific Installation

Install as a development dependency:

```bash
composer require --dev yangweijie/c-to-php-ffi-converter
```

## Quick Start

### Basic Usage

Generate PHP wrappers from a C header file:

```bash
c-to-php-ffi generate /path/to/header.h --output ./generated --namespace MyLib
```

### With Library File

Generate wrappers and specify the shared library:

```bash
c-to-php-ffi generate /path/to/header.h \
    --output ./generated \
    --namespace MyLib \
    --library /path/to/libmylib.so
```

### Using Configuration File

Create a configuration file `config.yaml`:

```yaml
header_files:
  - /path/to/header1.h
  - /path/to/header2.h
library_file: /path/to/libmylib.so
output_path: ./generated
namespace: MyLib\FFI
validation:
  enable_parameter_validation: true
  enable_type_conversion: true
```

Run with configuration:

```bash
c-to-php-ffi generate --config config.yaml
```

## Configuration

### CLI Options

- `--output, -o`: Output directory for generated files
- `--namespace, -n`: PHP namespace for generated classes
- `--library, -l`: Path to shared library file
- `--config, -c`: Path to YAML configuration file
- `--exclude`: Patterns to exclude from generation
- `--verbose, -v`: Enable verbose output

### Configuration File Format

```yaml
# Header files to process
header_files:
  - /path/to/header1.h
  - /path/to/header2.h

# Shared library file
library_file: /path/to/library.so

# Output configuration
output_path: ./generated
namespace: MyProject\FFI

# Validation settings
validation:
  enable_parameter_validation: true
  enable_type_conversion: true
  custom_validation_rules: []

# Generation settings
generation:
  generate_documentation: true
  generate_examples: true
  include_phpdoc: true

# Exclusion patterns
exclude_patterns:
  - "internal_*"
  - "_private_*"
```

## Generated Code Structure

The tool generates the following structure:

```
generated/
├── Classes/
│   ├── MathLibrary.php      # Main wrapper class
│   └── Structs/
│       └── Point.php        # Struct wrapper classes
├── Constants/
│   └── MathConstants.php    # Constants definitions
├── Documentation/
│   ├── README.md           # Usage documentation
│   └── Examples/
│       └── BasicUsage.php  # Usage examples
└── bootstrap.php           # Autoloader and initialization
```

## Usage Examples

### Basic Function Calls

```php
<?php
require_once 'generated/bootstrap.php';

use MyLib\MathLibrary;

$math = new MathLibrary();

// Call C function with automatic validation
$result = $math->add(5, 3); // Returns 8

// Work with structs
$point = $math->createPoint(10.5, 20.3);
echo $point->x; // 10.5
```

### Error Handling

```php
<?php
use MyLib\MathLibrary;
use Yangweijie\CWrapper\Exception\ValidationException;

$math = new MathLibrary();

try {
    // This will throw ValidationException if parameters are invalid
    $result = $math->divide(10, 0);
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage();
}
```

## Development

### Running Tests

```bash
# Run all tests
composer test

# Run unit tests only
composer test:unit

# Run integration tests only
composer test:integration
```

### Code Quality

```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix

# Run static analysis
composer phpstan

# Run all quality checks
composer quality
```

### Building from Source

1. Clone the repository:
```bash
git clone https://github.com/yangweijie/c-to-php-ffi-converter.git
cd c-to-php-ffi-converter
```

2. Install dependencies:
```bash
composer install
```

3. Run tests:
```bash
composer test
```

4. Build executable:
```bash
chmod +x bin/c-to-php-ffi
```

## Troubleshooting

### Common Issues

**FFI Extension Not Enabled**
```
Error: FFI extension is not enabled
```
Solution: Enable the FFI extension in your php.ini:
```ini
extension=ffi
ffi.enable=true
```

**Library Not Found**
```
Error: Cannot load library: /path/to/lib.so
```
Solution: Ensure the library path is correct and the library is compiled for your system architecture.

**Header File Not Found**
```
Error: Cannot read header file: /path/to/header.h
```
Solution: Verify the header file path and ensure you have read permissions.

### Getting Help

- Check the [documentation](docs/)
- Search [existing issues](https://github.com/yangweijie/c-to-php-ffi-converter/issues)
- Create a [new issue](https://github.com/yangweijie/c-to-php-ffi-converter/issues/new)

## Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- Built on top of [klitsche/ffigen](https://github.com/klitsche/ffigen)
- Uses [Symfony Console](https://symfony.com/doc/current/components/console.html) for CLI interface
- Template engine powered by [Twig](https://twig.symfony.com/)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes and version history.