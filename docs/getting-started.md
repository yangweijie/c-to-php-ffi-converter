# Getting Started

This guide will help you get up and running with the C-to-PHP FFI Converter quickly.

## Prerequisites

Before you begin, ensure you have:

- PHP 8.1 or higher
- FFI extension enabled
- Composer installed
- A C library with header files

## Installation

### Quick Install

```bash
composer global require yangweijie/c-to-php-ffi-converter
```

For detailed installation instructions, see [INSTALL.md](../INSTALL.md).

## Your First Wrapper

Let's create a simple wrapper for a math library.

### Step 1: Create a Simple C Library

Create `math.h`:
```c
#ifndef MATH_H
#define MATH_H

// Simple math functions
int add(int a, int b);
int multiply(int a, int b);
double divide(double a, double b);

// Constants
#define PI 3.14159265359
#define E  2.71828182846

#endif
```

Create `math.c`:
```c
#include "math.h"

int add(int a, int b) {
    return a + b;
}

int multiply(int a, int b) {
    return a * b;
}

double divide(double a, double b) {
    if (b == 0.0) return 0.0;
    return a / b;
}
```

Compile the library:
```bash
gcc -shared -fPIC -o libmath.so math.c
```

### Step 2: Generate PHP Wrapper

```bash
c-to-php-ffi generate math.h \
    --output ./generated \
    --namespace MyMath \
    --library ./libmath.so
```

### Step 3: Use the Generated Wrapper

```php
<?php
require_once 'generated/bootstrap.php';

use MyMath\MathLibrary;

$math = new MathLibrary();

// Use the wrapper
echo $math->add(5, 3) . "\n";        // Output: 8
echo $math->multiply(4, 7) . "\n";   // Output: 28
echo $math->divide(10.0, 3.0) . "\n"; // Output: 3.3333333333333

// Access constants
echo MathLibrary::PI . "\n";         // Output: 3.14159265359
```

## Configuration Options

### Command Line Options

- `--output, -o`: Output directory for generated files
- `--namespace, -n`: PHP namespace for generated classes
- `--library, -l`: Path to shared library file
- `--config, -c`: Path to YAML configuration file

### Configuration File

Create `config.yaml`:
```yaml
header_files:
  - math.h
library_file: ./libmath.so
output_path: ./generated
namespace: MyMath
validation:
  enable_parameter_validation: true
  enable_type_conversion: true
```

Use with:
```bash
c-to-php-ffi generate --config config.yaml
```

## Generated Structure

The tool generates:

```
generated/
├── bootstrap.php           # Autoloader and setup
├── Classes/
│   └── MathLibrary.php    # Main wrapper class
├── Constants/
│   └── MathConstants.php  # Constants definitions
└── Documentation/
    ├── README.md          # Usage guide
    └── Examples/
        └── BasicUsage.php # Usage examples
```

## Error Handling

The generated wrappers include automatic error handling:

```php
<?php
use MyMath\MathLibrary;
use Yangweijie\CWrapper\Exception\ValidationException;

$math = new MathLibrary();

try {
    // This will validate parameters
    $result = $math->add("invalid", 5);
} catch (ValidationException $e) {
    echo "Error: " . $e->getMessage();
}
```

## Next Steps

- Read the [Configuration Guide](configuration.md) for advanced options
- Check out [Examples](examples/) for more complex use cases
- See [API Reference](api-reference.md) for complete documentation
- Learn about [Advanced Usage](advanced-usage.md) features

## Common Patterns

### Working with Structs

If your C library uses structs, they'll be converted to PHP classes:

```c
// C struct
typedef struct {
    int x;
    int y;
} Point;

Point create_point(int x, int y);
```

```php
// Generated PHP
$point = $math->createPoint(10, 20);
echo $point->x; // 10
echo $point->y; // 20
```

### Parameter Validation

The tool automatically validates parameters:

```php
// Type validation
$math->add(5, 3);        // ✓ Valid
$math->add("5", 3);      // ✗ ValidationException

// Range validation (if configured)
$math->divide(10, 0);    // ✗ ValidationException (division by zero)
```

### Memory Management

FFI pointers are handled automatically:

```c
char* get_string();
void free_string(char* str);
```

```php
$str = $math->getString();
// Memory is automatically managed
// No need to call free_string manually
```

## Troubleshooting

### Common Issues

**"FFI extension not loaded"**
```bash
# Check if FFI is enabled
php -m | grep -i ffi

# Enable in php.ini
extension=ffi
ffi.enable=true
```

**"Library not found"**
```bash
# Check library path
ldd libmath.so

# Set LD_LIBRARY_PATH if needed
export LD_LIBRARY_PATH=/path/to/library:$LD_LIBRARY_PATH
```

For more troubleshooting, see [troubleshooting.md](troubleshooting.md).

## Getting Help

- Check [examples](examples/) for working code
- Read [troubleshooting guide](troubleshooting.md)
- Search [GitHub issues](https://github.com/yangweijie/c-to-php-ffi-converter/issues)
- Ask in [GitHub discussions](https://github.com/yangweijie/c-to-php-ffi-converter/discussions)