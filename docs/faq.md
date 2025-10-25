# Frequently Asked Questions (FAQ)

## General Questions

### Q: What is the C-to-PHP FFI Converter?

A: The C-to-PHP FFI Converter is a tool that automatically generates object-oriented PHP wrapper classes from C libraries. It builds on top of klitsche/ffigen to provide enhanced functionality including parameter validation, error handling, and comprehensive documentation generation.

### Q: How is this different from klitsche/ffigen?

A: While klitsche/ffigen generates low-level FFI bindings (constants.php and Methods.php trait), our tool creates complete object-oriented wrapper classes with:
- Parameter validation and type checking
- Comprehensive error handling
- Automatic documentation generation
- User-friendly CLI interface
- Configuration management
- Better integration patterns

### Q: What C libraries are supported?

A: The tool works with most standard C libraries that:
- Have well-formed header files
- Use standard C data types
- Don't rely heavily on complex macros or preprocessor magic
- Are compiled as shared libraries (.so, .dll, .dylib)

## Installation and Setup

### Q: What are the system requirements?

A: You need:
- PHP 8.1 or higher
- FFI extension enabled
- Composer
- C compiler (for building example libraries)
- The target C library compiled as a shared library

### Q: How do I enable the FFI extension?

A: Add these lines to your php.ini:
```ini
extension=ffi
ffi.enable=true
```
Then restart your web server or PHP-FPM if applicable.

### Q: Can I use this in production?

A: Yes, the generated wrappers are production-ready. However, always:
- Test thoroughly with your specific use case
- Enable parameter validation during development
- Handle exceptions appropriately
- Monitor memory usage with large datasets

### Q: Does this work on Windows?

A: Yes, the tool works on Windows, Linux, and macOS. On Windows:
- Use .dll files instead of .so files
- Ensure Visual C++ Redistributable is installed
- Consider using WSL for a Linux-like environment

## Usage Questions

### Q: How do I handle C structs?

A: C structs are automatically converted to PHP classes:

```c
// C struct
typedef struct {
    int x;
    int y;
} Point;
```

```php
// Generated PHP class
$point = new Point();
$point->x = 10;
$point->y = 20;
```

### Q: What about function pointers and callbacks?

A: Function pointers require manual handling. The tool generates basic wrappers, but callback implementation needs custom code. Consider creating C wrapper functions that accept simple parameters instead of function pointers.

### Q: How do I handle memory management?

A: The tool provides automatic memory management for most cases:
- Simple return values are handled automatically
- String returns are converted to PHP strings
- For complex memory patterns, you may need manual management

### Q: Can I customize the generated code?

A: Yes, you can:
- Modify Twig templates in the generator
- Post-process generated files
- Use configuration options to control generation
- Extend generated classes in your own code

### Q: How do I handle large C libraries?

A: For large libraries:
- Use exclusion patterns to skip unnecessary functions
- Split generation into multiple smaller libraries
- Increase PHP memory limit during generation
- Consider generating only the functions you need

## Configuration Questions

### Q: What configuration options are available?

A: Key options include:
- `header_files`: C header files to process
- `library_file`: Shared library path
- `output_path`: Where to generate PHP files
- `namespace`: PHP namespace for generated classes
- `validation`: Parameter validation settings
- `exclude_patterns`: Functions/types to skip

### Q: How do I exclude certain functions?

A: Use exclusion patterns in your config:
```yaml
exclude_patterns:
  - "internal_*"
  - "_private_*"
  - "test_*"
```

### Q: Can I use multiple header files?

A: Yes, list them in your configuration:
```yaml
header_files:
  - header1.h
  - header2.h
  - subdir/header3.h
```

### Q: How do I handle header dependencies?

A: The tool automatically resolves dependencies. Ensure:
- All required headers are accessible
- System headers are installed
- Include paths are correct

## Error Handling

### Q: What should I do if generation fails?

A: Check for:
- Valid header file syntax
- Accessible library file
- Correct file permissions
- Sufficient memory (increase with `-d memory_limit=1G`)
- Missing dependencies

### Q: How do I debug FFI issues?

A: Enable debug mode:
```bash
DEBUG=1 c-to-php-ffi generate header.h --verbose
```

Also check:
- FFI extension is loaded (`php -m | grep ffi`)
- Library can be loaded (`ldd library.so`)
- Correct architecture (32-bit vs 64-bit)

### Q: What about "symbol not found" errors?

A: This usually means:
- Library wasn't compiled with the function
- Function name mangling (use `nm -D library.so` to check symbols)
- Missing library dependencies
- Incorrect library path

## Performance Questions

### Q: How fast are the generated wrappers?

A: Performance depends on:
- FFI overhead (generally good for computational tasks)
- Parameter validation (can be disabled)
- Function call frequency
- Data transfer size

For high-performance scenarios, consider disabling validation in production.

### Q: Can I optimize the generated code?

A: Yes:
- Disable parameter validation for production
- Use type conversion sparingly
- Cache library instances
- Minimize data copying between PHP and C

### Q: How much memory does this use?

A: Memory usage depends on:
- Size of the C library
- Amount of data being processed
- Number of concurrent operations
- PHP memory management

Monitor with `memory_get_usage()` and adjust `memory_limit` as needed.

## Advanced Usage

### Q: Can I extend the generated classes?

A: Yes, you can extend or compose with generated classes:
```php
class MyMathLibrary extends GeneratedMathLibrary {
    public function advancedFunction($param) {
        // Your custom logic
        return $this->basicFunction($param);
    }
}
```

### Q: How do I handle version differences in C libraries?

A: Use different configurations for different versions:
```yaml
# config-v1.yaml
library_file: ./libmath-v1.so
namespace: MyLib\V1

# config-v2.yaml  
library_file: ./libmath-v2.so
namespace: MyLib\V2
```

### Q: Can I use this with C++ libraries?

A: Not directly. For C++ libraries:
- Create C wrapper functions with `extern "C"`
- Use only C-compatible types in the interface
- Handle C++ exceptions in the wrapper layer

### Q: How do I handle threading?

A: FFI and threading considerations:
- PHP FFI is not thread-safe by default
- Avoid sharing FFI instances between threads
- Use process-based parallelism instead of threads
- Consider using separate processes for concurrent access

## Troubleshooting

### Q: Why am I getting "Class 'FFI' not found"?

A: The FFI extension is not enabled. Add to php.ini:
```ini
extension=ffi
ffi.enable=true
```

### Q: Why is generation very slow?

A: Common causes:
- Large header files with many includes
- Complex dependency resolution
- Insufficient memory
- Slow disk I/O

Try increasing memory limit and using exclusion patterns.

### Q: How do I report bugs?

A: When reporting issues, include:
- Complete error message
- System information (OS, PHP version)
- Sample header file (if possible)
- Configuration used
- Steps to reproduce

Create an issue at: https://github.com/yangweijie/c-to-php-ffi-converter/issues

## Best Practices

### Q: What are the recommended practices?

A: Follow these guidelines:
- Enable validation during development
- Use descriptive namespaces
- Test generated wrappers thoroughly
- Handle exceptions appropriately
- Document any manual modifications
- Keep C interfaces simple
- Use version control for generated code

### Q: How should I structure my project?

A: Recommended structure:
```
project/
├── c-library/          # C source code
├── config/             # Converter configurations
├── generated/          # Generated PHP wrappers
├── src/               # Your PHP application code
└── tests/             # Tests for both C and PHP code
```

### Q: Should I commit generated code?

A: It depends on your workflow:
- **Commit**: For stable APIs, easier deployment
- **Don't commit**: For frequently changing APIs, cleaner repository

If you don't commit, ensure your build process regenerates the wrappers.

## Getting Help

### Q: Where can I get more help?

A: Resources available:
- [Documentation](README.md) - Comprehensive guides
- [Examples](examples/) - Working code samples
- [GitHub Issues](https://github.com/yangweijie/c-to-php-ffi-converter/issues) - Bug reports and feature requests
- [GitHub Discussions](https://github.com/yangweijie/c-to-php-ffi-converter/discussions) - General questions
- [Troubleshooting Guide](troubleshooting.md) - Common issues and solutions

### Q: How can I contribute?

A: Contributions are welcome:
- Report bugs and suggest features
- Submit pull requests
- Improve documentation
- Add examples
- Help other users

See [CONTRIBUTING.md](../CONTRIBUTING.md) for details.

### Q: Is there a community?

A: Join the community:
- GitHub Discussions for questions and ideas
- Issues for bug reports and feature requests
- Stack Overflow with the `c-to-php-ffi-converter` tag
- Follow the project for updates

---

*Don't see your question here? Check the [troubleshooting guide](troubleshooting.md) or ask in [GitHub Discussions](https://github.com/yangweijie/c-to-php-ffi-converter/discussions).*