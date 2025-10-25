#include "math_library.h"
#include <stdlib.h>
#include <string.h>
#include <math.h>

// Global error state
static MathError last_error = MATH_SUCCESS;

// Basic mathematical operations
int math_add(int a, int b) {
    last_error = MATH_SUCCESS;
    return a + b;
}

int math_subtract(int a, int b) {
    last_error = MATH_SUCCESS;
    return a - b;
}

int math_multiply(int a, int b) {
    last_error = MATH_SUCCESS;
    return a * b;
}

float math_divide(float a, float b) {
    if (b == 0.0f) {
        last_error = MATH_ERROR_DIVISION_BY_ZERO;
        return 0.0f;
    }
    last_error = MATH_SUCCESS;
    return a / b;
}

double math_power(double base, double exponent) {
    last_error = MATH_SUCCESS;
    return pow(base, exponent);
}

double math_sqrt(double value) {
    if (value < 0) {
        last_error = MATH_ERROR_INVALID_ARGUMENT;
        return 0.0;
    }
    last_error = MATH_SUCCESS;
    return sqrt(value);
}

// Array operations
int math_sum_array(const int* array, size_t length) {
    if (!array) {
        last_error = MATH_ERROR_NULL_POINTER;
        return 0;
    }
    
    last_error = MATH_SUCCESS;
    int sum = 0;
    for (size_t i = 0; i < length; i++) {
        sum += array[i];
    }
    return sum;
}

float math_average_array(const int* array, size_t length) {
    if (!array || length == 0) {
        last_error = MATH_ERROR_INVALID_ARGUMENT;
        return 0.0f;
    }
    
    int sum = math_sum_array(array, length);
    if (last_error != MATH_SUCCESS) {
        return 0.0f;
    }
    
    return (float)sum / (float)length;
}

int math_find_max(const int* array, size_t length) {
    if (!array || length == 0) {
        last_error = MATH_ERROR_INVALID_ARGUMENT;
        return 0;
    }
    
    last_error = MATH_SUCCESS;
    int max = array[0];
    for (size_t i = 1; i < length; i++) {
        if (array[i] > max) {
            max = array[i];
        }
    }
    return max;
}

int math_find_min(const int* array, size_t length) {
    if (!array || length == 0) {
        last_error = MATH_ERROR_INVALID_ARGUMENT;
        return 0;
    }
    
    last_error = MATH_SUCCESS;
    int min = array[0];
    for (size_t i = 1; i < length; i++) {
        if (array[i] < min) {
            min = array[i];
        }
    }
    return min;
}

// String operations
size_t math_string_length(const char* str) {
    if (!str) {
        last_error = MATH_ERROR_NULL_POINTER;
        return 0;
    }
    
    last_error = MATH_SUCCESS;
    return strlen(str);
}

char* math_string_reverse(const char* str) {
    if (!str) {
        last_error = MATH_ERROR_NULL_POINTER;
        return NULL;
    }
    
    size_t len = strlen(str);
    char* reversed = malloc(len + 1);
    if (!reversed) {
        last_error = MATH_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    for (size_t i = 0; i < len; i++) {
        reversed[i] = str[len - 1 - i];
    }
    reversed[len] = '\0';
    
    last_error = MATH_SUCCESS;
    return reversed;
}

int math_string_compare(const char* str1, const char* str2) {
    if (!str1 || !str2) {
        last_error = MATH_ERROR_NULL_POINTER;
        return 0;
    }
    
    last_error = MATH_SUCCESS;
    return strcmp(str1, str2);
}

// Geometric operations
double math_distance_2d(const Point2D* p1, const Point2D* p2) {
    if (!p1 || !p2) {
        last_error = MATH_ERROR_NULL_POINTER;
        return 0.0;
    }
    
    last_error = MATH_SUCCESS;
    double dx = p2->x - p1->x;
    double dy = p2->y - p1->y;
    return sqrt(dx * dx + dy * dy);
}

double math_distance_3d(const Point3D* p1, const Point3D* p2) {
    if (!p1 || !p2) {
        last_error = MATH_ERROR_NULL_POINTER;
        return 0.0;
    }
    
    last_error = MATH_SUCCESS;
    double dx = p2->x - p1->x;
    double dy = p2->y - p1->y;
    double dz = p2->z - p1->z;
    return sqrt(dx * dx + dy * dy + dz * dz);
}

double math_circle_area(const Circle* circle) {
    if (!circle) {
        last_error = MATH_ERROR_NULL_POINTER;
        return 0.0;
    }
    
    if (circle->radius < 0) {
        last_error = MATH_ERROR_INVALID_ARGUMENT;
        return 0.0;
    }
    
    last_error = MATH_SUCCESS;
    return MATH_PI * circle->radius * circle->radius;
}

double math_circle_circumference(const Circle* circle) {
    if (!circle) {
        last_error = MATH_ERROR_NULL_POINTER;
        return 0.0;
    }
    
    if (circle->radius < 0) {
        last_error = MATH_ERROR_INVALID_ARGUMENT;
        return 0.0;
    }
    
    last_error = MATH_SUCCESS;
    return 2.0 * MATH_PI * circle->radius;
}

// Point array operations
PointArray* math_create_point_array(size_t initial_capacity) {
    if (initial_capacity == 0) {
        last_error = MATH_ERROR_INVALID_ARGUMENT;
        return NULL;
    }
    
    PointArray* array = malloc(sizeof(PointArray));
    if (!array) {
        last_error = MATH_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    array->points = malloc(sizeof(Point2D) * initial_capacity);
    if (!array->points) {
        free(array);
        last_error = MATH_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    array->count = 0;
    array->capacity = initial_capacity;
    last_error = MATH_SUCCESS;
    return array;
}

void math_destroy_point_array(PointArray* array) {
    if (!array) {
        last_error = MATH_ERROR_NULL_POINTER;
        return;
    }
    
    if (array->points) {
        free(array->points);
    }
    free(array);
    last_error = MATH_SUCCESS;
}

int math_add_point(PointArray* array, const Point2D* point) {
    if (!array || !point) {
        last_error = MATH_ERROR_NULL_POINTER;
        return -1;
    }
    
    if (array->count >= array->capacity) {
        last_error = MATH_ERROR_INDEX_OUT_OF_BOUNDS;
        return -1;
    }
    
    array->points[array->count] = *point;
    array->count++;
    last_error = MATH_SUCCESS;
    return 0;
}

Point2D* math_get_point(const PointArray* array, size_t index) {
    if (!array) {
        last_error = MATH_ERROR_NULL_POINTER;
        return NULL;
    }
    
    if (index >= array->count) {
        last_error = MATH_ERROR_INDEX_OUT_OF_BOUNDS;
        return NULL;
    }
    
    last_error = MATH_SUCCESS;
    return &array->points[index];
}

size_t math_get_point_count(const PointArray* array) {
    if (!array) {
        last_error = MATH_ERROR_NULL_POINTER;
        return 0;
    }
    
    last_error = MATH_SUCCESS;
    return array->count;
}

// Error handling
MathError math_get_last_error(void) {
    return last_error;
}

const char* math_get_error_message(MathError error) {
    switch (error) {
        case MATH_SUCCESS:
            return "Success";
        case MATH_ERROR_NULL_POINTER:
            return "Null pointer error";
        case MATH_ERROR_INVALID_ARGUMENT:
            return "Invalid argument";
        case MATH_ERROR_DIVISION_BY_ZERO:
            return "Division by zero";
        case MATH_ERROR_OUT_OF_MEMORY:
            return "Out of memory";
        case MATH_ERROR_INDEX_OUT_OF_BOUNDS:
            return "Index out of bounds";
        default:
            return "Unknown error";
    }
}

// Advanced operations with callbacks
void math_sort_array_with_callback(int* array, size_t length, MathCompareCallback compare) {
    if (!array || !compare || length == 0) {
        last_error = MATH_ERROR_NULL_POINTER;
        return;
    }
    
    // Simple bubble sort for testing
    for (size_t i = 0; i < length - 1; i++) {
        for (size_t j = 0; j < length - i - 1; j++) {
            if (compare(&array[j], &array[j + 1]) > 0) {
                int temp = array[j];
                array[j] = array[j + 1];
                array[j + 1] = temp;
            }
        }
    }
    
    last_error = MATH_SUCCESS;
}

void math_process_with_progress(int* array, size_t length, MathProgressCallback progress) {
    if (!array || !progress || length == 0) {
        last_error = MATH_ERROR_NULL_POINTER;
        return;
    }
    
    for (size_t i = 0; i < length; i++) {
        // Simulate some processing
        array[i] = array[i] * 2;
        
        // Report progress
        double prog = (double)(i + 1) / (double)length;
        progress(prog);
    }
    
    last_error = MATH_SUCCESS;
}