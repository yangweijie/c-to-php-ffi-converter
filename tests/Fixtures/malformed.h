/* Malformed C header file for testing */
#ifndef MALFORMED_H
#define MALFORMED_H

// Missing semicolon
int broken_function(int param)

// Unmatched braces
struct broken_struct {
    int field1;
    // missing closing brace

// Invalid preprocessor directive
#invalid_directive

// Valid content mixed with broken content
#define VALID_CONSTANT 42
int valid_function(int param);

// Incomplete typedef
typedef struct incomplete_struct

#endif