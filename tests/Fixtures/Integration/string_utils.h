#ifndef STRING_UTILS_H
#define STRING_UTILS_H

#include <stddef.h>

/**
 * String Utilities Library - Sample C library for string manipulation testing
 */

// Constants
#define MAX_STRING_LENGTH 4096
#define STRING_UTILS_VERSION "1.0.0"

// String manipulation functions
char* string_duplicate(const char* str);
char* string_concatenate(const char* str1, const char* str2);
char* string_substring(const char* str, size_t start, size_t length);
char* string_to_upper(const char* str);
char* string_to_lower(const char* str);
char* string_trim(const char* str);

// String analysis functions
size_t string_count_chars(const char* str, char ch);
size_t string_count_words(const char* str);
int string_starts_with(const char* str, const char* prefix);
int string_ends_with(const char* str, const char* suffix);
int string_contains(const char* str, const char* substring);

// String array operations
typedef struct {
    char** strings;
    size_t count;
    size_t capacity;
} StringArray;

StringArray* string_array_create(size_t initial_capacity);
void string_array_destroy(StringArray* array);
int string_array_add(StringArray* array, const char* str);
char* string_array_get(const StringArray* array, size_t index);
size_t string_array_size(const StringArray* array);
char* string_array_join(const StringArray* array, const char* separator);
StringArray* string_split(const char* str, const char* delimiter);

// String formatting
char* string_format_int(int value);
char* string_format_float(float value, int precision);
int string_parse_int(const char* str);
float string_parse_float(const char* str);

// Error codes
typedef enum {
    STRING_SUCCESS = 0,
    STRING_ERROR_NULL_POINTER = -1,
    STRING_ERROR_INVALID_ARGUMENT = -2,
    STRING_ERROR_OUT_OF_MEMORY = -3,
    STRING_ERROR_INDEX_OUT_OF_BOUNDS = -4,
    STRING_ERROR_PARSE_ERROR = -5
} StringError;

StringError string_get_last_error(void);
const char* string_get_error_message(StringError error);

#endif // STRING_UTILS_H