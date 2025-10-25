# 入门指南

本指南将帮助您快速启动并运行 C-to-PHP FFI 转换器。

## 先决条件

在开始之前，请确保您具备以下条件：

- PHP 8.1 或更高版本
- 启用 FFI 扩展
- 已安装 Composer
- 带有头文件的 C 库

## 安装

### 快速安装

```bash
composer global require yangweijie/c-to-php-ffi-converter
```

有关详细安装说明，请参见 [INSTALL.md](../INSTALL.md)。

## 第一个包装器

让我们为一个数学库创建一个简单的包装器。

### 步骤 1：创建一个简单的 C 库

创建 `math.h`：
```c
#ifndef MATH_H
#define MATH_H

// 简单的数学函数
int add(int a, int b);
int multiply(int a, int b);
double divide(double a, double b);

// 常量
#define PI 3.14159265359
#define E  2.71828182846

#endif
```

创建 `math.c`：
```c
#include "math.h"

int add(int a, int b) {
    return a + b;
}

int multiply(int a, int b) {
    return a * b;
}

double divide(double a, double b) {
    if (b == 0.0) return 0.0;
    return a / b;
}
```

编译库：
```bash
gcc -shared -fPIC -o libmath.so math.c
```

### 步骤 2：生成 PHP 包装器

```bash
c-to-php-ffi generate math.h \
    --output ./generated \
    --namespace MyMath \
    --library ./libmath.so
```

### 步骤 3：使用生成的包装器

```php
<?php
require_once 'generated/bootstrap.php';

use MyMath\MathLibrary;

$math = new MathLibrary();

// 使用包装器
echo $math->add(5, 3) . "\n";        // 输出: 8
echo $math->multiply(4, 7) . "\n";   // 输出: 28
echo $math->divide(10.0, 3.0) . "\n"; // 输出: 3.3333333333333

// 访问常量
echo MathLibrary::PI . "\n";         // 输出: 3.14159265359
```

## 配置选项

### 命令行选项

- `--output, -o`：生成文件的输出目录
- `--namespace, -n`：生成类的 PHP 命名空间
- `--library, -l`：共享库文件路径
- `--config, -c`：YAML 配置文件路径

### 配置文件

创建 `config.yaml`：
```yaml
header_files:
  - math.h
library_file: ./libmath.so
output_path: ./generated
namespace: MyMath
validation:
  enable_parameter_validation: true
  enable_type_conversion: true
```

使用方法：
```bash
c-to-php-ffi generate --config config.yaml
```

## 生成的结构

工具生成以下结构：

```
generated/
├── bootstrap.php           # 自动加载和设置
├── Classes/
│   └── MathLibrary.php    # 主包装类
├── Constants/
│   └── MathConstants.php  # 常量定义
└── Documentation/
    ├── README.md          # 使用指南
    └── Examples/
        └── BasicUsage.php # 使用示例
```

## 错误处理

生成的包装器包含自动错误处理：

```php
<?php
use MyMath\MathLibrary;
use Yangweijie\CWrapper\Exception\ValidationException;

$math = new MathLibrary();

try {
    // 这将验证参数
    $result = $math->add("invalid", 5);
} catch (ValidationException $e) {
    echo "错误: " . $e->getMessage();
}
```

## 下一步

- 阅读 [配置指南](configuration.md) 了解高级选项
- 查看 [示例](examples/) 了解更复杂的用例
- 参见 [API 参考](api-reference.md) 获取完整文档
- 了解 [高级用法](advanced-usage.md) 功能

## 常见模式

### 使用结构体

如果您的 C 库使用结构体，它们将被转换为 PHP 类：

```c
// C 结构体
typedef struct {
    int x;
    int y;
} Point;

Point create_point(int x, int y);
```

```php
// 生成的 PHP
$point = $math->createPoint(10, 20);
echo $point->x; // 10
echo $point->y; // 20
```

### 参数验证

工具会自动验证参数：

```php
// 类型验证
$math->add(5, 3);        // ✓ 有效
$math->add("5", 3);      // ✗ ValidationException

// 范围验证（如果已配置）
$math->divide(10, 0);    // ✗ ValidationException（除零）
```

### 内存管理

FFI 指针会自动处理：

```c
char* get_string();
void free_string(char* str);
```

```php
$str = $math->getString();
// 内存会自动管理
// 无需手动调用 free_string
```

## 故障排除

### 常见问题

**"FFI 扩展未加载"**
```bash
# 检查 FFI 是否已启用
php -m | grep -i ffi

# 在 php.ini 中启用
extension=ffi
ffi.enable=true
```

**"库未找到"**
```bash
# 检查库路径
ldd libmath.so

# 如果需要，设置 LD_LIBRARY_PATH
export LD_LIBRARY_PATH=/path/to/library:$LD_LIBRARY_PATH
```

有关更多故障排除信息，请参见 [troubleshooting.md](troubleshooting_ZH.md)。

## 获取帮助

- 查看 [示例](examples/) 获取工作代码
- 阅读 [故障排除指南](troubleshooting_ZH.md)
- 搜索 [GitHub 问题](https://github.com/yangweijie/c-to-php-ffi-converter/issues)
- 在 [GitHub 讨论](https://github.com/yangweijie/c-to-php-ffi-converter/discussions) 中提问

[English Guide](getting-started.md)