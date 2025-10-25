#include "math.h"

int add(int a, int b) {
    return a + b;
}

int subtract(int a, int b) {
    return a - b;
}

int multiply(int a, int b) {
    return a * b;
}

double divide(double a, double b) {
    if (b == 0.0) {
        return 0.0; // Return 0 for division by zero
    }
    return a / b;
}

double power(double base, int exponent) {
    if (exponent == 0) {
        return 1.0;
    }
    
    double result = 1.0;
    int abs_exp = exponent < 0 ? -exponent : exponent;
    
    for (int i = 0; i < abs_exp; i++) {
        result *= base;
    }
    
    return exponent < 0 ? 1.0 / result : result;
}

double square_root(double x) {
    if (x < 0.0) {
        return -1.0; // Error: negative input
    }
    
    if (x == 0.0 || x == 1.0) {
        return x;
    }
    
    // Simple Newton's method implementation
    double guess = x / 2.0;
    double epsilon = 0.000001;
    
    while (1) {
        double new_guess = (guess + x / guess) / 2.0;
        if (new_guess - guess < epsilon && guess - new_guess < epsilon) {
            break;
        }
        guess = new_guess;
    }
    
    return guess;
}

long factorial(int n) {
    if (n < 0) {
        return -1; // Error: negative input
    }
    
    if (n == 0 || n == 1) {
        return 1;
    }
    
    long result = 1;
    for (int i = 2; i <= n; i++) {
        result *= i;
    }
    
    return result;
}

int is_prime(int n) {
    if (n <= 1) {
        return 0; // Not prime
    }
    
    if (n <= 3) {
        return 1; // 2 and 3 are prime
    }
    
    if (n % 2 == 0 || n % 3 == 0) {
        return 0; // Divisible by 2 or 3
    }
    
    // Check for divisors from 5 to sqrt(n)
    for (int i = 5; i * i <= n; i += 6) {
        if (n % i == 0 || n % (i + 2) == 0) {
            return 0;
        }
    }
    
    return 1; // Prime
}