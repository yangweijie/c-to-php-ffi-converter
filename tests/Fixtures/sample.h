#ifndef SAMPLE_H
#define SAMPLE_H

/**
 * Sample C header file for testing
 */

// Simple function declaration
int add(int a, int b);

// Function with pointer parameter
void process_array(int* arr, size_t length);

// Structure definition
typedef struct {
    int x;
    int y;
} Point;

// Constants
#define MAX_SIZE 1024
#define PI 3.14159

#endif // SAMPLE_H