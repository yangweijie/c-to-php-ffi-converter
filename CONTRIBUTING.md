# Contributing to C-to-PHP FFI Converter

Thank you for your interest in contributing to the C-to-PHP FFI Converter! This document provides guidelines and information for contributors.

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When creating a bug report, include:

- **Clear title and description**
- **Steps to reproduce** the issue
- **Expected vs actual behavior**
- **Environment details** (PHP version, OS, etc.)
- **Code samples** or test cases if applicable

### Suggesting Enhancements

Enhancement suggestions are welcome! Please provide:

- **Clear title and description** of the enhancement
- **Use case** explaining why this would be useful
- **Proposed implementation** if you have ideas
- **Examples** of how it would work

### Pull Requests

1. **Fork the repository** and create your branch from `main`
2. **Follow coding standards** (see below)
3. **Add tests** for new functionality
4. **Update documentation** as needed
5. **Ensure all tests pass**
6. **Submit a pull request**

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Git
- C compiler (gcc/clang) for testing
- Docker (optional, for integration tests)

### Local Development

1. **Clone the repository:**
```bash
git clone https://github.com/yangweijie/c-to-php-ffi-converter.git
cd c-to-php-ffi-converter
```

2. **Install dependencies:**
```bash
composer install
```

3. **Run tests to verify setup:**
```bash
composer test
```

4. **Set up pre-commit hooks (optional):**
```bash
composer cs-fix
composer phpstan
```

## Coding Standards

### PHP Standards

- Follow **PSR-12** coding standard
- Use **strict types** (`declare(strict_types=1);`)
- Write **comprehensive PHPDoc** comments
- Use **type hints** for all parameters and return values

### Code Style

Run code style checks:
```bash
composer cs-check
```

Fix code style issues:
```bash
composer cs-fix
```

### Static Analysis

Run PHPStan analysis:
```bash
composer phpstan
```

## Testing Guidelines

### Test Structure

```
tests/
├── Unit/           # Unit tests for individual classes
├── Integration/    # Integration tests for component interaction
└── Fixtures/       # Test data and sample files
```

### Writing Tests

- **Unit tests** should test individual classes in isolation
- **Integration tests** should test component interactions
- **Use descriptive test names** that explain what is being tested
- **Follow AAA pattern**: Arrange, Act, Assert
- **Mock external dependencies** in unit tests
- **Use real data** in integration tests when possible

### Running Tests

```bash
# All tests
composer test

# Unit tests only
composer test:unit

# Integration tests only
composer test:integration

# Specific test file
./vendor/bin/phpunit tests/Unit/Config/ConfigLoaderTest.php
```

### Test Coverage

Aim for high test coverage, especially for:
- Core business logic
- Error handling paths
- Configuration validation
- CLI interface functionality

## Documentation

### Code Documentation

- Write clear **PHPDoc comments** for all public methods
- Include **parameter descriptions** and **return value documentation**
- Add **usage examples** for complex functionality
- Document **exceptions** that may be thrown

### User Documentation

- Update **README.md** for new features
- Add **examples** in the `docs/examples/` directory
- Update **configuration documentation** for new options
- Include **troubleshooting** information for common issues

## Architecture Guidelines

### Design Principles

- **Single Responsibility**: Each class should have one reason to change
- **Dependency Injection**: Use constructor injection for dependencies
- **Interface Segregation**: Create focused interfaces
- **Open/Closed**: Open for extension, closed for modification

### Code Organization

```
src/
├── Analyzer/       # Header file analysis
├── Config/         # Configuration management
├── Console/        # CLI interface
├── Documentation/  # Documentation generation
├── Exception/      # Exception hierarchy
├── Generator/      # Code generation
├── Integration/    # External tool integration
├── Logging/        # Logging and reporting
└── Validation/     # Parameter validation
```

### Adding New Features

1. **Create interfaces** before implementations
2. **Add configuration options** if needed
3. **Implement core logic** with proper error handling
4. **Add comprehensive tests**
5. **Update documentation**
6. **Add CLI options** if applicable

## Release Process

### Version Numbering

We follow [Semantic Versioning](https://semver.org/):
- **MAJOR**: Incompatible API changes
- **MINOR**: New functionality (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Release Checklist

- [ ] All tests pass
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] Version bumped in appropriate files
- [ ] Tag created and pushed
- [ ] Release notes written

## Getting Help

### Communication Channels

- **GitHub Issues**: Bug reports and feature requests
- **GitHub Discussions**: General questions and discussions
- **Email**: Direct contact with maintainers (see README)

### Resources

- **Documentation**: See `docs/` directory
- **Examples**: See `examples/` directory
- **Tests**: See `tests/` directory for usage examples
- **API Reference**: Generated from PHPDoc comments

## Recognition

Contributors will be recognized in:
- **CONTRIBUTORS.md** file
- **Release notes** for significant contributions
- **GitHub contributors** page

## License

By contributing to this project, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing to the C-to-PHP FFI Converter! Your contributions help make this tool better for everyone.