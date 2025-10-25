/* Sample C header file for testing */
#ifndef SAMPLE_H
#define SAMPLE_H

#ifdef __cplusplus
extern "C" {
#endif

/* Simple function declarations */
int add(int a, int b);
double multiply(double x, double y);
char* get_version(void);

/* Constants */
#define MAX_BUFFER_SIZE 1024
#define PI 3.14159

/* Simple struct */
typedef struct {
    int x;
    int y;
} Point;

/* Function with struct parameter */
double distance(Point p1, Point p2);

#ifdef __cplusplus
}
#endif

#endif /* SAMPLE_H */