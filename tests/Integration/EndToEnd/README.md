# End-to-End Integration Tests

This directory contains comprehensive end-to-end tests for the C-to-PHP FFI Converter tool. These tests verify the complete workflow from C header files to working PHP wrapper classes.

## Test Structure

### Test Classes

1. **CompleteWorkflowTest.php**
   - Tests the complete generation workflow from C to PHP
   - Verifies CLI interface functionality
   - Tests configuration file processing
   - Validates generated wrapper classes
   - Tests documentation generation

2. **GeneratedWrapperTest.php**
   - Tests that generated wrapper classes work with actual C libraries
   - Verifies FFI integration and function calls
   - Tests error handling and validation
   - Tests memory management and cleanup
   - Tests callback function handling

3. **CLIInterfaceTest.php**
   - Tests CLI interface with real-world scenarios
   - Tests command-line argument processing
   - Tests configuration file handling
   - Tests error handling for invalid inputs
   - Tests output formatting options

4. **RealWorldScenariosTest.php**
   - Tests complex C headers with dependencies
   - Tests error recovery with malformed headers
   - Tests performance with large projects
   - Tests cross-platform compatibility
   - Tests integration with existing PHP projects

### Test Groups

Tests are organized into groups for selective execution:

- `@group e2e` - Basic end-to-end workflow tests
- `@group cli` - CLI interface tests
- `@group wrapper` - Generated wrapper functionality tests
- `@group real-world` - Real-world scenario tests
- `@group performance` - Performance and memory tests

## Prerequisites

### System Requirements

- PHP 8.1+ with FFI extension enabled
- GCC or compatible C compiler
- Make build system
- Composer for PHP dependencies

### Environment Setup

1. **Install PHP with FFI extension**:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php8.1-ffi
   
   # macOS with Homebrew
   brew install php
   
   # Enable FFI in php.ini
   extension=ffi
   ffi.enable=1
   ```

2. **Install build tools**:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install build-essential
   
   # macOS
   xcode-select --install
   ```

3. **Install Composer dependencies**:
   ```bash
   cd /path/to/project
   composer install
   ```

## Running Tests

### Quick Start

Run all end-to-end tests:
```bash
./run_e2e_tests.sh
```

### Selective Test Execution

Run specific test suites:
```bash
# Basic workflow tests only
./run_e2e_tests.sh --basic

# CLI interface tests only
./run_e2e_tests.sh --cli

# Generated wrapper tests only
./run_e2e_tests.sh --wrapper

# Real-world scenario tests only
./run_e2e_tests.sh --real-world

# Verbose output
./run_e2e_tests.sh --verbose
```

### Manual PHPUnit Execution

Run tests directly with PHPUnit:
```bash
# All end-to-end tests
vendor/bin/phpunit --configuration tests/Integration/EndToEnd/phpunit.xml

# Specific test class
vendor/bin/phpunit tests/Integration/EndToEnd/CompleteWorkflowTest.php

# Specific test group
vendor/bin/phpunit --group e2e tests/Integration/EndToEnd/

# With coverage
vendor/bin/phpunit --coverage-html coverage/ tests/Integration/EndToEnd/
```

### Docker-based Testing

Use Docker for consistent testing environment:
```bash
cd tests/Fixtures/Integration

# Build and run tests
docker-compose up integration-test

# Interactive development
docker-compose up -d integration-dev
docker-compose exec integration-dev bash
```

## Test Scenarios

### 1. Complete Workflow Tests

**Scenario**: Generate PHP wrappers from C libraries
- **Input**: C header files and shared libraries
- **Process**: Full generation workflow via CLI
- **Verification**: 
  - Generated PHP classes exist
  - Classes have correct structure and methods
  - Documentation is generated
  - Wrapper classes work with actual C libraries

**Example**:
```bash
c-to-php-ffi generate math_library.h \
  --library libmath_library.so \
  --output ./generated \
  --namespace Math\\FFI
```

### 2. CLI Interface Tests

**Scenario**: Test command-line interface functionality
- **Input**: Various CLI arguments and configurations
- **Process**: Execute CLI commands with different options
- **Verification**:
  - Commands execute successfully
  - Error handling works correctly
  - Output formatting is correct
  - Help and version information is displayed

### 3. Generated Wrapper Tests

**Scenario**: Verify generated wrappers work with C libraries
- **Input**: Generated PHP wrapper classes
- **Process**: Load and execute wrapper methods
- **Verification**:
  - FFI calls work correctly
  - Parameter validation functions
  - Error handling is proper
  - Memory management is correct

**Example**:
```php
$math = new Math\FFI\MathLibrary();
$result = $math->mathAdd(5, 3); // Should return 8
```

### 4. Real-World Scenarios

**Scenario**: Test complex real-world use cases
- **Input**: Complex C headers with dependencies
- **Process**: Handle edge cases and error conditions
- **Verification**:
  - Complex structures are handled
  - Dependencies are resolved
  - Error recovery works
  - Performance is acceptable

## Test Data

### C Libraries Used

1. **Math Library** (`math_library.h/c`)
   - Basic arithmetic operations
   - Array processing functions
   - Geometric calculations
   - Error handling with detailed codes
   - Callback function support

2. **String Utils Library** (`string_utils.h/c`)
   - String manipulation functions
   - String array operations
   - String formatting and parsing
   - Memory management

### Test Configurations

- **Basic Configuration**: Minimal settings for simple generation
- **Full Configuration**: Complete settings with all options enabled
- **Validation Configuration**: Settings with parameter validation enabled
- **Documentation Configuration**: Settings with documentation generation

## Expected Outputs

### Generated Files

For each test scenario, the following files should be generated:

1. **PHP Wrapper Classes**:
   - `MathLibrary.php` - Main wrapper class
   - `Point2D.php` - Structure wrapper classes
   - `Circle.php` - Additional structure classes

2. **Documentation**:
   - `README.md` - Usage documentation
   - `examples/` - Code examples directory
   - `composer.json` - Composer configuration

3. **Support Files**:
   - Configuration files
   - Template files (if customized)

### Verification Criteria

1. **Functional Verification**:
   - All C functions are wrapped
   - Parameter types are correctly mapped
   - Return values are properly handled
   - Error conditions are managed

2. **Quality Verification**:
   - Generated code follows PSR standards
   - PHPDoc comments are complete
   - Error messages are descriptive
   - Performance is acceptable

3. **Integration Verification**:
   - Wrappers work with actual C libraries
   - Memory management is correct
   - No memory leaks occur
   - Callbacks function properly

## Troubleshooting

### Common Issues

1. **FFI Extension Not Available**:
   ```
   Error: FFI extension is not loaded
   ```
   **Solution**: Enable FFI extension in php.ini

2. **C Library Build Failures**:
   ```
   Error: Failed to build test libraries
   ```
   **Solution**: Install build tools (gcc, make)

3. **Permission Issues**:
   ```
   Error: Cannot write to output directory
   ```
   **Solution**: Check directory permissions

4. **Memory Issues**:
   ```
   Error: Allowed memory size exhausted
   ```
   **Solution**: Increase PHP memory limit

### Debug Mode

Enable debug mode for detailed output:
```bash
export DEBUG=1
./run_e2e_tests.sh --verbose
```

### Log Files

Test execution logs are available in:
- `tests/output/e2e-junit.xml` - JUnit format results
- `tests/output/e2e-testdox.html` - HTML test documentation
- `tests/output/debug.log` - Debug information (if enabled)

## Performance Benchmarks

### Expected Performance

- **Small Projects** (< 50 functions): < 5 seconds
- **Medium Projects** (50-200 functions): < 15 seconds  
- **Large Projects** (200+ functions): < 60 seconds

### Memory Usage

- **Peak Memory**: < 256MB for typical projects
- **Memory Growth**: Should be linear with project size
- **Memory Cleanup**: No significant leaks after generation

## Contributing

### Adding New Tests

1. Create test class extending `PHPUnit\Framework\TestCase`
2. Add appropriate test groups with `@group` annotations
3. Follow naming convention: `*Test.php`
4. Update this README with new test scenarios

### Test Data

1. Add new C libraries to `tests/Fixtures/Integration/`
2. Update Makefile to build new libraries
3. Create corresponding configuration files
4. Document expected behavior

### Continuous Integration

Tests are designed to run in CI environments:
- GitHub Actions
- GitLab CI
- Jenkins
- Travis CI

Example CI configuration available in `.github/workflows/e2e-tests.yml`.