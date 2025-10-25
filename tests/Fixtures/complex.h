#ifndef COMPLEX_H
#define COMPLEX_H

#include <stdio.h>
#include <stdlib.h>
#include "sample.h"

/**
 * Complex header file for testing various C constructs
 */

// Constants
#define MAX_BUFFER_SIZE 4096
#define PI 3.14159265359
#define VERSION_STRING "1.0.0"
#define DEBUG_MODE 1

// Function declarations
int calculate(int a, int b, float factor);
void* allocate_memory(size_t size);
char* process_string(const char* input, int* length);
void callback_function(void (*callback)(int, const char*));

// Structure definitions
typedef struct {
    int id;
    char* name;
    float value;
} DataRecord;

typedef union {
    int intValue;
    float floatValue;
    char charValue;
} ValueUnion;

typedef struct {
    DataRecord* records;
    size_t count;
    ValueUnion metadata;
} DataCollection;

// Function with complex parameters
int process_data(DataCollection* collection, const DataRecord* filter, size_t filter_count);

#endif // COMPLEX_H