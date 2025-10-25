#ifndef MATH_LIBRARY_H
#define MATH_LIBRARY_H

#include <stddef.h>

/**
 * Math Library - Sample C library for integration testing
 * This library provides various mathematical operations and data structures
 */

// Constants
#define MATH_PI 3.14159265359
#define MAX_ARRAY_SIZE 1000
#define LIBRARY_VERSION_MAJOR 1
#define LIBRARY_VERSION_MINOR 0

// Basic mathematical operations
int math_add(int a, int b);
int math_subtract(int a, int b);
int math_multiply(int a, int b);
float math_divide(float a, float b);
double math_power(double base, double exponent);
double math_sqrt(double value);

// Array operations
int math_sum_array(const int* array, size_t length);
float math_average_array(const int* array, size_t length);
int math_find_max(const int* array, size_t length);
int math_find_min(const int* array, size_t length);

// String operations
size_t math_string_length(const char* str);
char* math_string_reverse(const char* str);
int math_string_compare(const char* str1, const char* str2);

// Structure definitions
typedef struct {
    double x;
    double y;
} Point2D;

typedef struct {
    double x;
    double y;
    double z;
} Point3D;

typedef struct {
    Point2D center;
    double radius;
} Circle;

typedef struct {
    Point2D* points;
    size_t count;
    size_t capacity;
} PointArray;

// Geometric operations
double math_distance_2d(const Point2D* p1, const Point2D* p2);
double math_distance_3d(const Point3D* p1, const Point3D* p2);
double math_circle_area(const Circle* circle);
double math_circle_circumference(const Circle* circle);

// Point array operations
PointArray* math_create_point_array(size_t initial_capacity);
void math_destroy_point_array(PointArray* array);
int math_add_point(PointArray* array, const Point2D* point);
Point2D* math_get_point(const PointArray* array, size_t index);
size_t math_get_point_count(const PointArray* array);

// Error handling
typedef enum {
    MATH_SUCCESS = 0,
    MATH_ERROR_NULL_POINTER = -1,
    MATH_ERROR_INVALID_ARGUMENT = -2,
    MATH_ERROR_DIVISION_BY_ZERO = -3,
    MATH_ERROR_OUT_OF_MEMORY = -4,
    MATH_ERROR_INDEX_OUT_OF_BOUNDS = -5
} MathError;

// Get last error
MathError math_get_last_error(void);
const char* math_get_error_message(MathError error);

// Callback function types
typedef void (*MathProgressCallback)(double progress);
typedef int (*MathCompareCallback)(const void* a, const void* b);

// Advanced operations with callbacks
void math_sort_array_with_callback(int* array, size_t length, MathCompareCallback compare);
void math_process_with_progress(int* array, size_t length, MathProgressCallback progress);

#endif // MATH_LIBRARY_H