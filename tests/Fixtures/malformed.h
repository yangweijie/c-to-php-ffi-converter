#ifndef MALFORMED_H
#define MALFORMED_H

// This header has intentional syntax errors for testing error handling

// Missing semicolon
int broken_function(int a, int b)

// Incomplete struct
typedef struct {
    int x;
    // Missing closing brace

// Invalid define
#define INVALID_DEFINE 

// Unclosed comment
/* This comment is not closed

int another_function(void);

#endif // MALFORMED_H