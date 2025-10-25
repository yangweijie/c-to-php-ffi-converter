# Integration Test Fixtures

This directory contains comprehensive test fixtures for integration testing of the C-to-PHP FFI Converter tool.

## Contents

### C Libraries

1. **Math Library** (`math_library.h/c`)
   - Basic mathematical operations (add, subtract, multiply, divide)
   - Array operations (sum, average, min, max)
   - String operations (length, reverse, compare)
   - Geometric operations (2D/3D distance, circle calculations)
   - Point array management
   - Error handling with detailed error codes
   - Callback function support

2. **String Utils Library** (`string_utils.h/c`)
   - String manipulation (duplicate, concatenate, substring)
   - Case conversion (upper, lower)
   - String trimming and analysis
   - String array operations
   - String formatting and parsing
   - Comprehensive error handling

### Test Programs

- `test_math.c` - Verification tests for math library
- `test_string.c` - Verification tests for string utils library

### Configuration Files

- `config_math.yaml` - Configuration for math library FFI generation
- `config_string.yaml` - Configuration for string utils library FFI generation

### Build System

- `Makefile` - Cross-platform build system for shared libraries
- `Dockerfile` - Container environment for consistent testing
- `docker-compose.yml` - Docker Compose configuration for different testing scenarios

## Usage

### Building Libraries Locally

```bash
cd tests/Fixtures/Integration
make all
```

This will build:
- `libmath_library.so` (or `.dylib` on macOS, `.dll` on Windows)
- `libstring_utils.so` (or `.dylib` on macOS, `.dll` on Windows)

### Running Tests

```bash
# Build and run C tests
make test

# Run individual tests
./test_math
./test_string
```

### Using Docker

```bash
# Build and run integration tests
docker-compose up integration-test

# Interactive development environment
docker-compose up -d integration-dev
docker-compose exec integration-dev bash

# Build libraries only
docker-compose up build-libs
```

### Installing Libraries

```bash
make install
```

This installs libraries to `../lib/` for use by integration tests.

## Library Features Tested

### Math Library Features
- ✅ Basic arithmetic operations
- ✅ Array processing functions
- ✅ String manipulation
- ✅ Geometric calculations
- ✅ Dynamic memory management (Point arrays)
- ✅ Error handling and reporting
- ✅ Callback function pointers
- ✅ Complex data structures (structs, unions, enums)

### String Utils Features
- ✅ String manipulation and analysis
- ✅ Dynamic string arrays
- ✅ String formatting and parsing
- ✅ Memory management
- ✅ Error handling
- ✅ Edge case handling (null pointers, empty strings)

## Integration Test Scenarios

These fixtures support testing:

1. **Header Analysis**: Complex C constructs parsing
2. **FFI Generation**: klitsche/ffigen integration
3. **Wrapper Generation**: PHP class generation from C functions
4. **Type Mapping**: C to PHP type conversion
5. **Parameter Validation**: Runtime parameter checking
6. **Error Handling**: Exception generation and handling
7. **Documentation Generation**: PHPDoc and README creation
8. **Memory Management**: Proper cleanup and resource handling
9. **Callback Functions**: Function pointer handling
10. **Cross-Platform Compatibility**: Linux, macOS, Windows support

## Requirements Coverage

This fixture set addresses the following requirements:

- **1.1**: Function signature extraction and mapping
- **1.2**: PHP method generation with FFI calls
- **2.1**: C struct to PHP class conversion
- **2.2**: Constant extraction and generation

## Platform Support

The build system supports:
- Linux (`.so` shared libraries)
- macOS (`.dylib` dynamic libraries)  
- Windows (`.dll` dynamic libraries)

## Dependencies

- GCC or compatible C compiler
- Make build system
- Math library (`-lm`)
- Docker (optional, for containerized testing)
- PHP with FFI extension (for runtime testing)