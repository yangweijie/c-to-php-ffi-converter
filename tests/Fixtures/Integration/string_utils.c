#include "string_utils.h"
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include <stdio.h>

// Global error state
static StringError last_error = STRING_SUCCESS;

// String manipulation functions
char* string_duplicate(const char* str) {
    if (!str) {
        last_error = STRING_ERROR_NULL_POINTER;
        return NULL;
    }
    
    size_t len = strlen(str);
    char* dup = malloc(len + 1);
    if (!dup) {
        last_error = STRING_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    strcpy(dup, str);
    last_error = STRING_SUCCESS;
    return dup;
}

char* string_concatenate(const char* str1, const char* str2) {
    if (!str1 || !str2) {
        last_error = STRING_ERROR_NULL_POINTER;
        return NULL;
    }
    
    size_t len1 = strlen(str1);
    size_t len2 = strlen(str2);
    char* result = malloc(len1 + len2 + 1);
    if (!result) {
        last_error = STRING_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    strcpy(result, str1);
    strcat(result, str2);
    last_error = STRING_SUCCESS;
    return result;
}

char* string_substring(const char* str, size_t start, size_t length) {
    if (!str) {
        last_error = STRING_ERROR_NULL_POINTER;
        return NULL;
    }
    
    size_t str_len = strlen(str);
    if (start >= str_len) {
        last_error = STRING_ERROR_INDEX_OUT_OF_BOUNDS;
        return NULL;
    }
    
    if (start + length > str_len) {
        length = str_len - start;
    }
    
    char* result = malloc(length + 1);
    if (!result) {
        last_error = STRING_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    strncpy(result, str + start, length);
    result[length] = '\0';
    last_error = STRING_SUCCESS;
    return result;
}

char* string_to_upper(const char* str) {
    if (!str) {
        last_error = STRING_ERROR_NULL_POINTER;
        return NULL;
    }
    
    size_t len = strlen(str);
    char* result = malloc(len + 1);
    if (!result) {
        last_error = STRING_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    for (size_t i = 0; i < len; i++) {
        result[i] = toupper(str[i]);
    }
    result[len] = '\0';
    
    last_error = STRING_SUCCESS;
    return result;
}

char* string_to_lower(const char* str) {
    if (!str) {
        last_error = STRING_ERROR_NULL_POINTER;
        return NULL;
    }
    
    size_t len = strlen(str);
    char* result = malloc(len + 1);
    if (!result) {
        last_error = STRING_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    for (size_t i = 0; i < len; i++) {
        result[i] = tolower(str[i]);
    }
    result[len] = '\0';
    
    last_error = STRING_SUCCESS;
    return result;
}

char* string_trim(const char* str) {
    if (!str) {
        last_error = STRING_ERROR_NULL_POINTER;
        return NULL;
    }
    
    // Find start of non-whitespace
    const char* start = str;
    while (*start && isspace(*start)) {
        start++;
    }
    
    // Find end of non-whitespace
    const char* end = str + strlen(str) - 1;
    while (end > start && isspace(*end)) {
        end--;
    }
    
    size_t len = end - start + 1;
    char* result = malloc(len + 1);
    if (!result) {
        last_error = STRING_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    strncpy(result, start, len);
    result[len] = '\0';
    
    last_error = STRING_SUCCESS;
    return result;
}

// String analysis functions
size_t string_count_chars(const char* str, char ch) {
    if (!str) {
        last_error = STRING_ERROR_NULL_POINTER;
        return 0;
    }
    
    size_t count = 0;
    for (const char* p = str; *p; p++) {
        if (*p == ch) {
            count++;
        }
    }
    
    last_error = STRING_SUCCESS;
    return count;
}

size_t string_count_words(const char* str) {
    if (!str) {
        last_error = STRING_ERROR_NULL_POINTER;
        return 0;
    }
    
    size_t count = 0;
    int in_word = 0;
    
    for (const char* p = str; *p; p++) {
        if (!isspace(*p)) {
            if (!in_word) {
                count++;
                in_word = 1;
            }
        } else {
            in_word = 0;
        }
    }
    
    last_error = STRING_SUCCESS;
    return count;
}

int string_starts_with(const char* str, const char* prefix) {
    if (!str || !prefix) {
        last_error = STRING_ERROR_NULL_POINTER;
        return 0;
    }
    
    last_error = STRING_SUCCESS;
    return strncmp(str, prefix, strlen(prefix)) == 0;
}

int string_ends_with(const char* str, const char* suffix) {
    if (!str || !suffix) {
        last_error = STRING_ERROR_NULL_POINTER;
        return 0;
    }
    
    size_t str_len = strlen(str);
    size_t suffix_len = strlen(suffix);
    
    if (suffix_len > str_len) {
        last_error = STRING_SUCCESS;
        return 0;
    }
    
    last_error = STRING_SUCCESS;
    return strcmp(str + str_len - suffix_len, suffix) == 0;
}

int string_contains(const char* str, const char* substring) {
    if (!str || !substring) {
        last_error = STRING_ERROR_NULL_POINTER;
        return 0;
    }
    
    last_error = STRING_SUCCESS;
    return strstr(str, substring) != NULL;
}

// String array operations
StringArray* string_array_create(size_t initial_capacity) {
    if (initial_capacity == 0) {
        last_error = STRING_ERROR_INVALID_ARGUMENT;
        return NULL;
    }
    
    StringArray* array = malloc(sizeof(StringArray));
    if (!array) {
        last_error = STRING_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    array->strings = malloc(sizeof(char*) * initial_capacity);
    if (!array->strings) {
        free(array);
        last_error = STRING_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    array->count = 0;
    array->capacity = initial_capacity;
    last_error = STRING_SUCCESS;
    return array;
}

void string_array_destroy(StringArray* array) {
    if (!array) {
        last_error = STRING_ERROR_NULL_POINTER;
        return;
    }
    
    for (size_t i = 0; i < array->count; i++) {
        free(array->strings[i]);
    }
    free(array->strings);
    free(array);
    last_error = STRING_SUCCESS;
}

int string_array_add(StringArray* array, const char* str) {
    if (!array || !str) {
        last_error = STRING_ERROR_NULL_POINTER;
        return -1;
    }
    
    if (array->count >= array->capacity) {
        last_error = STRING_ERROR_INDEX_OUT_OF_BOUNDS;
        return -1;
    }
    
    array->strings[array->count] = string_duplicate(str);
    if (!array->strings[array->count]) {
        return -1; // Error already set by string_duplicate
    }
    
    array->count++;
    last_error = STRING_SUCCESS;
    return 0;
}

char* string_array_get(const StringArray* array, size_t index) {
    if (!array) {
        last_error = STRING_ERROR_NULL_POINTER;
        return NULL;
    }
    
    if (index >= array->count) {
        last_error = STRING_ERROR_INDEX_OUT_OF_BOUNDS;
        return NULL;
    }
    
    last_error = STRING_SUCCESS;
    return array->strings[index];
}

size_t string_array_size(const StringArray* array) {
    if (!array) {
        last_error = STRING_ERROR_NULL_POINTER;
        return 0;
    }
    
    last_error = STRING_SUCCESS;
    return array->count;
}

// String formatting
char* string_format_int(int value) {
    char* result = malloc(32); // Enough for any int
    if (!result) {
        last_error = STRING_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    sprintf(result, "%d", value);
    last_error = STRING_SUCCESS;
    return result;
}

char* string_format_float(float value, int precision) {
    if (precision < 0 || precision > 10) {
        last_error = STRING_ERROR_INVALID_ARGUMENT;
        return NULL;
    }
    
    char* result = malloc(64); // Enough for any float
    if (!result) {
        last_error = STRING_ERROR_OUT_OF_MEMORY;
        return NULL;
    }
    
    char format[16];
    sprintf(format, "%%.%df", precision);
    sprintf(result, format, value);
    
    last_error = STRING_SUCCESS;
    return result;
}

int string_parse_int(const char* str) {
    if (!str) {
        last_error = STRING_ERROR_NULL_POINTER;
        return 0;
    }
    
    char* endptr;
    int result = strtol(str, &endptr, 10);
    
    if (*endptr != '\0') {
        last_error = STRING_ERROR_PARSE_ERROR;
        return 0;
    }
    
    last_error = STRING_SUCCESS;
    return result;
}

float string_parse_float(const char* str) {
    if (!str) {
        last_error = STRING_ERROR_NULL_POINTER;
        return 0.0f;
    }
    
    char* endptr;
    float result = strtof(str, &endptr);
    
    if (*endptr != '\0') {
        last_error = STRING_ERROR_PARSE_ERROR;
        return 0.0f;
    }
    
    last_error = STRING_SUCCESS;
    return result;
}

// Error handling
StringError string_get_last_error(void) {
    return last_error;
}

const char* string_get_error_message(StringError error) {
    switch (error) {
        case STRING_SUCCESS:
            return "Success";
        case STRING_ERROR_NULL_POINTER:
            return "Null pointer error";
        case STRING_ERROR_INVALID_ARGUMENT:
            return "Invalid argument";
        case STRING_ERROR_OUT_OF_MEMORY:
            return "Out of memory";
        case STRING_ERROR_INDEX_OUT_OF_BOUNDS:
            return "Index out of bounds";
        case STRING_ERROR_PARSE_ERROR:
            return "Parse error";
        default:
            return "Unknown error";
    }
}