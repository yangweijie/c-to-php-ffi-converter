#include "string_utils.h"
#include <stdio.h>
#include <assert.h>
#include <string.h>
#include <stdlib.h>

/**
 * Simple test program to verify string utils library functionality
 */

int main() {
    printf("Testing String Utils Library...\n");
    
    // Test string duplication
    char* dup = string_duplicate("hello");
    assert(dup != NULL);
    assert(strcmp(dup, "hello") == 0);
    free(dup);
    
    // Test string concatenation
    char* concat = string_concatenate("hello", " world");
    assert(concat != NULL);
    assert(strcmp(concat, "hello world") == 0);
    free(concat);
    
    // Test substring
    char* sub = string_substring("hello world", 6, 5);
    assert(sub != NULL);
    assert(strcmp(sub, "world") == 0);
    free(sub);
    
    // Test case conversion
    char* upper = string_to_upper("hello");
    assert(upper != NULL);
    assert(strcmp(upper, "HELLO") == 0);
    free(upper);
    
    char* lower = string_to_lower("WORLD");
    assert(lower != NULL);
    assert(strcmp(lower, "world") == 0);
    free(lower);
    
    // Test trimming
    char* trimmed = string_trim("  hello world  ");
    assert(trimmed != NULL);
    assert(strcmp(trimmed, "hello world") == 0);
    free(trimmed);
    
    // Test string analysis
    assert(string_count_chars("hello", 'l') == 2);
    assert(string_count_words("hello world test") == 3);
    assert(string_starts_with("hello world", "hello") == 1);
    assert(string_ends_with("hello world", "world") == 1);
    assert(string_contains("hello world", "lo wo") == 1);
    
    // Test string array
    StringArray* array = string_array_create(10);
    assert(array != NULL);
    assert(string_array_size(array) == 0);
    
    assert(string_array_add(array, "first") == 0);
    assert(string_array_add(array, "second") == 0);
    assert(string_array_size(array) == 2);
    
    char* first = string_array_get(array, 0);
    assert(first != NULL);
    assert(strcmp(first, "first") == 0);
    
    string_array_destroy(array);
    
    // Test formatting
    char* int_str = string_format_int(42);
    assert(int_str != NULL);
    assert(strcmp(int_str, "42") == 0);
    free(int_str);
    
    char* float_str = string_format_float(3.14159f, 2);
    assert(float_str != NULL);
    assert(strcmp(float_str, "3.14") == 0);
    free(float_str);
    
    // Test parsing
    assert(string_parse_int("123") == 123);
    assert(string_parse_float("3.14") == 3.14f);
    
    printf("All string utils library tests passed!\n");
    return 0;
}