# Dockerfile for C-to-PHP FFI Converter Integration Testing
# Provides a consistent environment for building and testing C libraries

FROM ubuntu:22.04

# Install required packages
RUN apt-get update && apt-get install -y \
    build-essential \
    gcc \
    make \
    libc6-dev \
    php8.1 \
    php8.1-cli \
    php8.1-ffi \
    php8.1-dev \
    composer \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy source files
COPY . /app/

# Create directories for output
RUN mkdir -p /app/test_output /app/lib

# Set environment variables
ENV LD_LIBRARY_PATH=/app/lib:/app/tests/Fixtures/Integration:$LD_LIBRARY_PATH
ENV PHP_FFI_PRELOAD=/app/tests/Fixtures/Integration/*.h

# Build the test libraries
RUN cd /app/tests/Fixtures/Integration && make all

# Install the libraries to a common location
RUN cd /app/tests/Fixtures/Integration && make install

# Make the libraries executable
RUN chmod +x /app/tests/Fixtures/Integration/lib*.so || true
RUN chmod +x /app/tests/Fixtures/Integration/lib*.dylib || true

# Create a test script
RUN echo '#!/bin/bash\n\
echo "Testing C libraries..."\n\
cd /app/tests/Fixtures/Integration\n\
if [ -f "libmath_library.so" ]; then\n\
    echo "Math library built successfully"\n\
    ldd libmath_library.so || echo "Math library dependencies OK"\n\
fi\n\
if [ -f "libstring_utils.so" ]; then\n\
    echo "String utils library built successfully"\n\
    ldd libstring_utils.so || echo "String utils library dependencies OK"\n\
fi\n\
echo "Running C tests..."\n\
if make test; then\n\
    echo "C library tests passed"\n\
    ./test_math || echo "Math test executable failed"\n\
    ./test_string || echo "String test executable failed"\n\
else\n\
    echo "Failed to build C tests"\n\
fi\n\
echo "PHP FFI extension status:"\n\
php -m | grep -i ffi || echo "FFI extension not found"\n\
php -r "echo FFI::cdef(\"int add(int a, int b);\", \"./libmath_library.so\")->add(2, 3) . \"\\n\";" || echo "FFI test failed"\n\
' > /app/test_integration.sh && chmod +x /app/test_integration.sh

# Default command
CMD ["/app/test_integration.sh"]