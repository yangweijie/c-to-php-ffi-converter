#ifndef DEPENDENCY1_H
#define DEPENDENCY1_H

#include "dependency2.h"

typedef struct {
    int value;
    Dependency2Type dep2;
} Dependency1Type;

void function_in_dep1(Dependency1Type* data);

#endif // DEPENDENCY1_H