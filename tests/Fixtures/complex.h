/* Complex C header file for testing */
#ifndef COMPLEX_H
#define COMPLEX_H

#include <stdio.h>
#include <stdlib.h>
#include "sample.h"

/* Complex constants */
#define MAX_BUFFER_SIZE 4096
#define PI 3.14159265359
#define VERSION_STRING "1.0.0"
#define DEBUG_MODE 1

/* Enums */
typedef enum {
    STATUS_OK = 0,
    STATUS_ERROR = 1,
    STATUS_PENDING = 2
} status_t;

/* Complex structures */
typedef struct {
    int id;
    char name[64];
    double value;
} Item;

typedef struct {
    Item* items;
    size_t count;
    size_t capacity;
} Collection;

/* Union type */
typedef union {
    int intValue;
    double doubleValue;
    char* stringValue;
} ValueUnion;

/* Function declarations */
int calculate(int a, double b, const char* operation);
void callback_function(void (*callback)(int, void*), void* data);
status_t process_collection(Collection* collection, ValueUnion* values);
Item* find_item_by_id(const Collection* collection, int id);
void cleanup_collection(Collection* collection);

/* Function pointer type */
typedef int (*compare_func_t)(const Item* a, const Item* b);
void sort_collection(Collection* collection, compare_func_t compare);

#endif /* COMPLEX_H */