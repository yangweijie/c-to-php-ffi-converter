# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release of C-to-PHP FFI Converter
- Automatic PHP FFI wrapper generation from C header files
- Integration with klitsche/ffigen for low-level binding generation
- Object-oriented wrapper classes with validation and error handling
- CLI interface with comprehensive configuration options
- YAML configuration file support
- Parameter validation engine with type checking
- Comprehensive error handling and exception hierarchy
- Documentation generation with PHPDoc comments and examples
- Dependency resolution for header files
- Template-based code generation using Twig
- Logging and progress reporting system
- Comprehensive test suite with unit and integration tests
- Docker-based testing environment for C library integration

### Features
- **Configuration Management**: Flexible configuration via CLI options or YAML files
- **Header Analysis**: Parse C header files to extract functions, structs, and constants
- **Wrapper Generation**: Generate object-oriented PHP classes from C definitions
- **Validation Engine**: Runtime parameter validation and type conversion
- **Documentation**: Automatic generation of PHPDoc comments and usage examples
- **Error Handling**: Comprehensive exception hierarchy with detailed error messages
- **CLI Interface**: User-friendly command-line interface with help and validation
- **Testing**: Extensive test coverage including end-to-end workflow testing

### Dependencies
- PHP 8.1+
- klitsche/ffigen ^0.8.1
- symfony/console ^5.0|^6.0|^7.0
- symfony/yaml ^5.0|^6.0|^7.0
- twig/twig ^3.0
- phpstan/phpdoc-parser ^1.0
- psr/log ^1.0|^2.0|^3.0

## [1.0.0] - TBD

### Added
- First stable release
- Complete feature set as described in requirements
- Production-ready code with comprehensive testing
- Full documentation and examples
- Distribution package ready for Composer

---

## Release Notes

### Version 1.0.0
This is the initial stable release of the C-to-PHP FFI Converter. The tool provides a complete solution for generating PHP FFI wrapper classes from C projects, addressing common pain points in manual FFI wrapper creation.

**Key Features:**
- Automated wrapper generation with validation
- Comprehensive error handling and logging
- Flexible configuration options
- Extensive documentation generation
- Production-ready code quality

**Breaking Changes:**
- None (initial release)

**Migration Guide:**
- This is the first release, no migration needed

**Known Issues:**
- None at release time

**Upgrade Path:**
- Install via Composer: `composer global require yangweijie/c-to-php-ffi-converter`