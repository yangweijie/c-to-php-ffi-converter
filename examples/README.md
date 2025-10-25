# Examples

This directory contains working examples demonstrating various use cases of the C-to-PHP FFI Converter.

## Available Examples

### [Basic Math Library](basic-math/)
A simple example showing how to wrap basic mathematical functions.
- Integer and floating-point operations
- Constants handling
- Basic error handling

### [String Utilities](string-utils/)
Demonstrates string manipulation functions and memory management.
- String processing functions
- Memory allocation and deallocation
- Character array handling

### [Complex Structures](complex-structs/)
Shows how to work with complex C data structures.
- Nested structures
- Arrays and pointers
- Structure initialization and manipulation

### [Real-World Library](real-world/)
A comprehensive example using a real C library (SQLite).
- Complex API wrapping
- Error handling patterns
- Resource management

## Running Examples

Each example directory contains:
- `README.md` - Specific instructions for that example
- `Makefile` - Build instructions for the C library
- `config.yaml` - Configuration for the converter
- `src/` - C source code
- `generated/` - Generated PHP wrappers (after running)
- `test.php` - Test script demonstrating usage

### Quick Start

1. Navigate to an example directory:
```bash
cd examples/basic-math
```

2. Build the C library:
```bash
make
```

3. Generate PHP wrappers:
```bash
c-to-php-ffi generate --config config.yaml
```

4. Run the test:
```bash
php test.php
```

## Example Structure

```
examples/
├── README.md              # This file
├── basic-math/            # Simple math functions
│   ├── README.md
│   ├── Makefile
│   ├── config.yaml
│   ├── src/
│   │   ├── math.h
│   │   └── math.c
│   ├── generated/         # Generated after running converter
│   └── test.php
├── string-utils/          # String manipulation
├── complex-structs/       # Complex data structures
└── real-world/           # Real library example
```

## Learning Path

1. **Start with [basic-math](basic-math/)** - Learn the fundamentals
2. **Try [string-utils](string-utils/)** - Understand memory management
3. **Explore [complex-structs](complex-structs/)** - Work with advanced data types
4. **Study [real-world](real-world/)** - See production patterns

## Common Patterns

### Error Handling
```php
try {
    $result = $library->someFunction($param);
} catch (ValidationException $e) {
    // Handle parameter validation errors
} catch (FFIException $e) {
    // Handle FFI-related errors
}
```

### Memory Management
```php
// Automatic memory management
$string = $library->getString();
// No need to manually free - handled automatically

// Manual memory management (when needed)
$ptr = $library->allocateMemory(1024);
// ... use pointer ...
$library->freeMemory($ptr);
```

### Working with Structs
```php
// Create struct
$point = $library->createPoint(10, 20);

// Access fields
echo $point->x; // 10
echo $point->y; // 20

// Modify fields
$point->x = 30;
$library->updatePoint($point);
```

## Contributing Examples

To contribute a new example:

1. Create a new directory under `examples/`
2. Follow the structure of existing examples
3. Include comprehensive documentation
4. Add test cases
5. Submit a pull request

### Example Template

```
new-example/
├── README.md          # Description and instructions
├── Makefile           # Build instructions
├── config.yaml        # Converter configuration
├── src/
│   ├── library.h      # C header file
│   └── library.c      # C implementation
└── test.php           # Usage demonstration
```

## Tips and Best Practices

### Configuration
- Use descriptive namespaces
- Enable validation for development
- Configure appropriate exclusion patterns
- Document custom validation rules

### Testing
- Test all generated functions
- Verify error handling
- Check memory management
- Test edge cases

### Documentation
- Include usage examples
- Document any manual modifications
- Explain complex patterns
- Provide troubleshooting tips

## Getting Help

If you have questions about the examples:

1. Check the specific example's README
2. Look at the test.php file for usage patterns
3. Search [GitHub issues](https://github.com/yangweijie/c-to-php-ffi-converter/issues)
4. Ask in [GitHub discussions](https://github.com/yangweijie/c-to-php-ffi-converter/discussions)