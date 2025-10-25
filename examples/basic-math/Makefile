# Makefile for Basic Math Library Example

# Compiler settings
CC = gcc
CFLAGS = -Wall -Wextra -fPIC -O2
LDFLAGS = -shared

# Source files
SRCDIR = src
SOURCES = $(SRCDIR)/math.c
HEADER = $(SRCDIR)/math.h

# Output library
LIBRARY = libmath.so

# Platform-specific settings
UNAME_S := $(shell uname -s)
ifeq ($(UNAME_S),Darwin)
    LIBRARY = libmath.dylib
    LDFLAGS = -shared -undefined dynamic_lookup
endif
ifeq ($(OS),Windows_NT)
    LIBRARY = math.dll
    LDFLAGS = -shared -Wl,--out-implib,libmath.a
endif

# Default target
all: $(LIBRARY)

# Build the shared library
$(LIBRARY): $(SOURCES) $(HEADER)
	@echo "Building $(LIBRARY)..."
	$(CC) $(CFLAGS) $(LDFLAGS) -o $(LIBRARY) $(SOURCES)
	@echo "Library built successfully!"

# Generate PHP wrappers
generate: $(LIBRARY)
	@echo "Generating PHP wrappers..."
	c-to-php-ffi generate --config config.yaml
	@echo "PHP wrappers generated in ./generated/"

# Test the library
test: $(LIBRARY) generate
	@echo "Running test..."
	php test.php

# Clean build artifacts
clean:
	rm -f $(LIBRARY)
	rm -f libmath.a  # Windows import library
	rm -rf generated/
	@echo "Cleaned build artifacts"

# Install dependencies (for development)
install-deps:
	@echo "Installing development dependencies..."
	@which gcc > /dev/null || (echo "Error: gcc not found. Please install build tools." && exit 1)
	@which php > /dev/null || (echo "Error: PHP not found. Please install PHP." && exit 1)
	@php -m | grep -q ffi || (echo "Error: PHP FFI extension not found. Please enable FFI." && exit 1)
	@which c-to-php-ffi > /dev/null || (echo "Error: c-to-php-ffi not found. Please install the converter." && exit 1)
	@echo "All dependencies are available!"

# Help target
help:
	@echo "Available targets:"
	@echo "  all          - Build the shared library (default)"
	@echo "  generate     - Build library and generate PHP wrappers"
	@echo "  test         - Build, generate, and run test"
	@echo "  clean        - Remove build artifacts"
	@echo "  install-deps - Check for required dependencies"
	@echo "  help         - Show this help message"

# Phony targets
.PHONY: all generate test clean install-deps help