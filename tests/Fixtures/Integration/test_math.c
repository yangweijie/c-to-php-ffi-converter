#include "math_library.h"
#include <stdio.h>
#include <assert.h>

/**
 * Simple test program to verify math library functionality
 */

int main() {
    printf("Testing Math Library...\n");
    
    // Test basic operations
    assert(math_add(5, 3) == 8);
    assert(math_subtract(10, 4) == 6);
    assert(math_multiply(6, 7) == 42);
    
    // Test division
    float result = math_divide(10.0f, 2.0f);
    assert(result == 5.0f);
    
    // Test division by zero
    result = math_divide(10.0f, 0.0f);
    assert(math_get_last_error() == MATH_ERROR_DIVISION_BY_ZERO);
    
    // Test array operations
    int arr[] = {1, 2, 3, 4, 5};
    assert(math_sum_array(arr, 5) == 15);
    assert(math_find_max(arr, 5) == 5);
    assert(math_find_min(arr, 5) == 1);
    
    // Test geometric operations
    Point2D p1 = {0.0, 0.0};
    Point2D p2 = {3.0, 4.0};
    double distance = math_distance_2d(&p1, &p2);
    assert(distance == 5.0);
    
    // Test circle operations
    Circle circle = {{0.0, 0.0}, 5.0};
    double area = math_circle_area(&circle);
    assert(area > 78.5 && area < 78.6); // Approximately PI * 5^2
    
    // Test point array
    PointArray* points = math_create_point_array(10);
    assert(points != NULL);
    assert(math_get_point_count(points) == 0);
    
    Point2D point = {1.0, 2.0};
    assert(math_add_point(points, &point) == 0);
    assert(math_get_point_count(points) == 1);
    
    Point2D* retrieved = math_get_point(points, 0);
    assert(retrieved != NULL);
    assert(retrieved->x == 1.0 && retrieved->y == 2.0);
    
    math_destroy_point_array(points);
    
    printf("All math library tests passed!\n");
    return 0;
}