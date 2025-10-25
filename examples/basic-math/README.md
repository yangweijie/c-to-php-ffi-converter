# Basic Math Library Example

This example demonstrates the basic usage of the C-to-PHP FFI Converter with a simple math library.

## What This Example Shows

- Basic function wrapping (add, subtract, multiply, divide)
- Constant definitions (PI, E)
- Parameter validation
- Error handling
- Simple data types (int, double)

## Files

- `src/math.h` - C header file with function declarations
- `src/math.c` - C implementation
- `config.yaml` - Converter configuration
- `Makefile` - Build instructions
- `test.php` - Usage demonstration

## Building and Running

### Step 1: Build the C Library

```bash
make
```

This compiles the C code into a shared library (`libmath.so` on Linux/macOS, `math.dll` on Windows).

### Step 2: Generate PHP Wrappers

```bash
c-to-php-ffi generate --config config.yaml
```

This creates the `generated/` directory with PHP wrapper classes.

### Step 3: Test the Wrappers

```bash
php test.php
```

## Generated Structure

After running the converter, you'll have:

```
generated/
├── bootstrap.php              # Autoloader and initialization
├── Classes/
│   └── MathLibrary.php       # Main wrapper class
├── Constants/
│   └── MathConstants.php     # Mathematical constants
└── Documentation/
    ├── README.md             # Usage documentation
    └── Examples/
        └── BasicUsage.php    # Code examples
```

## Usage Examples

### Basic Operations

```php
<?php
require_once 'generated/bootstrap.php';

use BasicMath\MathLibrary;

$math = new MathLibrary();

// Basic arithmetic
echo $math->add(5, 3) . "\n";        // Output: 8
echo $math->subtract(10, 4) . "\n";  // Output: 6
echo $math->multiply(6, 7) . "\n";   // Output: 42
echo $math->divide(15.0, 3.0) . "\n"; // Output: 5.0
```

### Using Constants

```php
<?php
use BasicMath\MathConstants;

echo "PI = " . MathConstants::PI . "\n";  // Output: PI = 3.14159265359
echo "E = " . MathConstants::E . "\n";    // Output: E = 2.71828182846
```

### Error Handling

```php
<?php
use BasicMath\MathLibrary;
use Yangweijie\CWrapper\Exception\ValidationException;

$math = new MathLibrary();

try {
    // This will throw ValidationException due to division by zero
    $result = $math->divide(10.0, 0.0);
} catch (ValidationException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    // This will throw ValidationException due to wrong parameter type
    $result = $math->add("not a number", 5);
} catch (ValidationException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## Configuration Explained

The `config.yaml` file contains:

```yaml
header_files:
  - src/math.h                    # Header file to process

library_file: ./libmath.so        # Compiled shared library

output_path: ./generated          # Where to put generated files

namespace: BasicMath              # PHP namespace for generated classes

validation:
  enable_parameter_validation: true    # Enable runtime validation
  enable_type_conversion: true         # Allow automatic type conversion

generation:
  generate_documentation: true         # Generate documentation
  generate_examples: true              # Generate usage examples
  include_phpdoc: true                # Include PHPDoc comments
```

## C Library Details

### Functions

The C library provides these functions:

```c
int add(int a, int b);              // Addition
int subtract(int a, int b);         // Subtraction
int multiply(int a, int b);         // Multiplication
double divide(double a, double b);  // Division (with zero check)
```

### Constants

```c
#define PI 3.14159265359
#define E  2.71828182846
```

### Implementation Notes

- The `divide` function includes a check for division by zero
- All functions use simple parameter types for clarity
- No complex memory management required

## Generated PHP Code

The converter generates:

### MathLibrary Class

```php
<?php
namespace BasicMath;

class MathLibrary
{
    private FFI $ffi;
    
    public function __construct()
    {
        $this->ffi = FFI::cdef(
            file_get_contents(__DIR__ . '/../definitions.h'),
            __DIR__ . '/../libmath.so'
        );
    }
    
    public function add(int $a, int $b): int
    {
        // Parameter validation
        // FFI call
        return $this->ffi->add($a, $b);
    }
    
    // ... other methods
}
```

### Constants Class

```php
<?php
namespace BasicMath;

class MathConstants
{
    public const PI = 3.14159265359;
    public const E = 2.71828182846;
}
```

## Customization

You can customize the generation by modifying `config.yaml`:

### Disable Validation

```yaml
validation:
  enable_parameter_validation: false
```

### Change Namespace

```yaml
namespace: MyMath\Library
```

### Add Exclusions

```yaml
exclude_patterns:
  - "internal_*"
  - "_private_*"
```

## Troubleshooting

### Library Not Found

If you get "library not found" errors:

```bash
# Check if library exists
ls -la libmath.so

# Set library path (Linux/macOS)
export LD_LIBRARY_PATH=.:$LD_LIBRARY_PATH

# Use absolute path in config
library_file: /absolute/path/to/libmath.so
```

### Compilation Errors

If `make` fails:

```bash
# Install build tools (Ubuntu/Debian)
sudo apt install build-essential

# Install build tools (CentOS/RHEL)
sudo yum groupinstall "Development Tools"

# Manual compilation
gcc -shared -fPIC -o libmath.so src/math.c
```

### FFI Errors

If you get FFI-related errors:

```bash
# Check FFI extension
php -m | grep -i ffi

# Enable FFI in php.ini
extension=ffi
ffi.enable=true
```

## Next Steps

After completing this example:

1. Try the [string-utils](../string-utils/) example for memory management
2. Explore [complex-structs](../complex-structs/) for advanced data types
3. Study the generated code to understand the patterns
4. Experiment with different configuration options

## Learning Objectives

By completing this example, you should understand:

- How to configure the converter
- Basic C to PHP type mapping
- Parameter validation concepts
- Error handling patterns
- Generated code structure
- How to use the generated wrappers

This foundation will help you work with more complex C libraries in real-world scenarios.