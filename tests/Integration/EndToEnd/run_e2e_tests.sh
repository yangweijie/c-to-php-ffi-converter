#!/bin/bash

# End-to-End Test Runner for C-to-PHP FFI Converter
# This script sets up the environment and runs comprehensive end-to-end tests

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Directories
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../../../" && pwd)"
FIXTURES_DIR="${SCRIPT_DIR}/../../Fixtures/Integration"
OUTPUT_DIR="${SCRIPT_DIR}/../../output"

echo -e "${BLUE}C-to-PHP FFI Converter - End-to-End Test Runner${NC}"
echo "=================================================="

# Function to print status messages
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check prerequisites
print_status "Checking prerequisites..."

# Check PHP
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed or not in PATH"
    exit 1
fi

# Check PHP FFI extension
if ! php -m | grep -q "FFI"; then
    print_warning "PHP FFI extension is not loaded - some tests may fail"
fi

# Check Composer
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed or not in PATH"
    exit 1
fi

# Check Make (for building test libraries)
if ! command -v make &> /dev/null; then
    print_error "Make is not installed or not in PATH"
    exit 1
fi

# Check GCC (for building test libraries)
if ! command -v gcc &> /dev/null; then
    print_error "GCC is not installed or not in PATH"
    exit 1
fi

print_success "All prerequisites are available"

# Install dependencies
print_status "Installing PHP dependencies..."
cd "${PROJECT_ROOT}"
composer install --no-interaction --prefer-dist --optimize-autoloader

# Create output directory
print_status "Setting up test environment..."
mkdir -p "${OUTPUT_DIR}"
rm -rf "${OUTPUT_DIR}"/*

# Build test libraries
print_status "Building test C libraries..."
cd "${FIXTURES_DIR}"

if [ -f "Makefile" ]; then
    make clean || true
    if make all; then
        print_success "Test libraries built successfully"
        
        # List built libraries
        echo "Built libraries:"
        ls -la lib*.so lib*.dylib lib*.dll 2>/dev/null || echo "  No shared libraries found"
    else
        print_error "Failed to build test libraries"
        exit 1
    fi
else
    print_error "Makefile not found in fixtures directory"
    exit 1
fi

# Set library path for runtime
export LD_LIBRARY_PATH="${FIXTURES_DIR}:${LD_LIBRARY_PATH}"
export DYLD_LIBRARY_PATH="${FIXTURES_DIR}:${DYLD_LIBRARY_PATH}"

# Run different test suites
cd "${SCRIPT_DIR}"

# Parse command line arguments
RUN_ALL=true
RUN_BASIC=false
RUN_CLI=false
RUN_WRAPPER=false
RUN_REAL_WORLD=false
VERBOSE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --basic)
            RUN_ALL=false
            RUN_BASIC=true
            shift
            ;;
        --cli)
            RUN_ALL=false
            RUN_CLI=true
            shift
            ;;
        --wrapper)
            RUN_ALL=false
            RUN_WRAPPER=true
            shift
            ;;
        --real-world)
            RUN_ALL=false
            RUN_REAL_WORLD=true
            shift
            ;;
        --verbose|-v)
            VERBOSE=true
            shift
            ;;
        --help|-h)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --basic      Run basic workflow tests only"
            echo "  --cli        Run CLI interface tests only"
            echo "  --wrapper    Run generated wrapper tests only"
            echo "  --real-world Run real-world scenario tests only"
            echo "  --verbose    Enable verbose output"
            echo "  --help       Show this help message"
            echo ""
            echo "If no specific test suite is specified, all tests will be run."
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Set PHPUnit options
PHPUNIT_OPTIONS="--configuration phpunit.xml"
if [ "$VERBOSE" = true ]; then
    PHPUNIT_OPTIONS="$PHPUNIT_OPTIONS --verbose"
fi

# Run test suites
if [ "$RUN_ALL" = true ] || [ "$RUN_BASIC" = true ]; then
    print_status "Running complete workflow tests..."
    if php "${PROJECT_ROOT}/vendor/bin/phpunit" $PHPUNIT_OPTIONS --group e2e CompleteWorkflowTest.php; then
        print_success "Complete workflow tests passed"
    else
        print_error "Complete workflow tests failed"
        exit 1
    fi
fi

if [ "$RUN_ALL" = true ] || [ "$RUN_CLI" = true ]; then
    print_status "Running CLI interface tests..."
    if php "${PROJECT_ROOT}/vendor/bin/phpunit" $PHPUNIT_OPTIONS --group cli CLIInterfaceTest.php; then
        print_success "CLI interface tests passed"
    else
        print_error "CLI interface tests failed"
        exit 1
    fi
fi

if [ "$RUN_ALL" = true ] || [ "$RUN_WRAPPER" = true ]; then
    print_status "Running generated wrapper tests..."
    if php "${PROJECT_ROOT}/vendor/bin/phpunit" $PHPUNIT_OPTIONS --group wrapper GeneratedWrapperTest.php; then
        print_success "Generated wrapper tests passed"
    else
        print_error "Generated wrapper tests failed"
        exit 1
    fi
fi

if [ "$RUN_ALL" = true ] || [ "$RUN_REAL_WORLD" = true ]; then
    print_status "Running real-world scenario tests..."
    if php "${PROJECT_ROOT}/vendor/bin/phpunit" $PHPUNIT_OPTIONS --group real-world RealWorldScenariosTest.php; then
        print_success "Real-world scenario tests passed"
    else
        print_error "Real-world scenario tests failed"
        exit 1
    fi
fi

# Optional: Run performance tests
if [ "$RUN_ALL" = true ]; then
    print_status "Running performance tests (optional)..."
    if php "${PROJECT_ROOT}/vendor/bin/phpunit" $PHPUNIT_OPTIONS --group performance; then
        print_success "Performance tests passed"
    else
        print_warning "Performance tests failed (this is optional)"
    fi
fi

# Generate test report
print_status "Generating test report..."
if [ -f "${OUTPUT_DIR}/e2e-testdox.html" ]; then
    print_success "Test report generated: ${OUTPUT_DIR}/e2e-testdox.html"
fi

if [ -f "${OUTPUT_DIR}/e2e-junit.xml" ]; then
    print_success "JUnit report generated: ${OUTPUT_DIR}/e2e-junit.xml"
fi

# Clean up
print_status "Cleaning up test artifacts..."
cd "${FIXTURES_DIR}"
make clean || true

print_success "All end-to-end tests completed successfully!"
echo ""
echo "Test artifacts are available in: ${OUTPUT_DIR}"
echo "Generated wrappers and documentation can be found in the test output directories."