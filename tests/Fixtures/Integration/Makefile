# Makefile for building test shared libraries
# This creates shared libraries for integration testing

CC = gcc
CFLAGS = -Wall -Wextra -fPIC -O2 -std=c99
LDFLAGS = -shared
LIBS = -lm

# Detect OS for shared library extension
UNAME_S := $(shell uname -s)
ifeq ($(UNAME_S),Linux)
    EXT = so
    LDFLAGS += -Wl,-soname,lib$@.$(EXT)
endif
ifeq ($(UNAME_S),Darwin)
    EXT = dylib
    LDFLAGS += -install_name lib$@.$(EXT)
endif
ifeq ($(OS),Windows_NT)
    EXT = dll
    LDFLAGS += -Wl,--out-implib,lib$@.dll.a
endif

# Default extension if not detected
ifndef EXT
    EXT = so
endif

# Library targets
LIBRARIES = libmath_library.$(EXT) libstring_utils.$(EXT)

# Source files
MATH_SOURCES = math_library.c
STRING_SOURCES = string_utils.c

# Object files
MATH_OBJECTS = $(MATH_SOURCES:.c=.o)
STRING_OBJECTS = $(STRING_SOURCES:.c=.o)

.PHONY: all clean test install

all: $(LIBRARIES)

# Math library
libmath_library.$(EXT): $(MATH_OBJECTS)
	$(CC) $(LDFLAGS) -o $@ $^ $(LIBS)

# String utils library
libstring_utils.$(EXT): $(STRING_OBJECTS)
	$(CC) $(LDFLAGS) -o $@ $^ $(LIBS)

# Object file compilation
%.o: %.c %.h
	$(CC) $(CFLAGS) -c $< -o $@

# Test compilation (for verification)
test: test_math test_string

test_math: test_math.c libmath_library.$(EXT)
	$(CC) $(CFLAGS) -o $@ $< -L. -lmath_library $(LIBS)

test_string: test_string.c libstring_utils.$(EXT)
	$(CC) $(CFLAGS) -o $@ $< -L. -lstring_utils

# Install libraries to a common location for testing
install: $(LIBRARIES)
	mkdir -p ../lib
	cp $(LIBRARIES) ../lib/

# Clean build artifacts
clean:
	rm -f *.o $(LIBRARIES) test_math test_string
	rm -rf ../lib

# Help target
help:
	@echo "Available targets:"
	@echo "  all      - Build all shared libraries"
	@echo "  clean    - Remove build artifacts"
	@echo "  test     - Build test executables"
	@echo "  install  - Install libraries to ../lib"
	@echo "  help     - Show this help message"
	@echo ""
	@echo "Libraries built:"
	@echo "  libmath_library.$(EXT)  - Mathematical operations library"
	@echo "  libstring_utils.$(EXT)  - String manipulation library"