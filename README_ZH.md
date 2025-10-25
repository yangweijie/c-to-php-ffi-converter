# C-to-PHP FFI 转换器

一个强大的 PHP 工具，可自动从 C 项目生成面向对象的 PHP FFI 包装类。基于 [klitsche/ffigen](https://github.com/klitsche/ffigen) 构建，提供了增强功能，包括参数验证、错误处理和全面的文档生成。

[English README](README.md)

## 功能特性

- **自动生成包装器**：将 C 头文件转换为 PHP FFI 包装类
- **参数验证**：C 函数参数的运行时类型检查和验证
- **错误处理**：具有描述性异常的全面错误处理
- **文档生成**：自动生成 PHPDoc 注释和使用示例
- **CLI 接口**：易于使用的命令行界面
- **灵活配置**：支持 YAML 配置文件和 CLI 选项
- **依赖解析**：自动处理头文件依赖关系

## 环境要求

- PHP 8.1 或更高版本
- 启用 PHP FFI 扩展
- Composer
- C 编译器（用于编译示例库）

## 安装

### 全局安装（推荐）

通过 Composer 全局安装：

```bash
composer global require yangweijie/c-to-php-ffi-converter
```

确保全局 Composer bin 目录在您的 PATH 中：

```bash
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

### 项目内安装

作为开发依赖安装：

```bash
composer require --dev yangweijie/c-to-php-ffi-converter
```

## 快速开始

### 基本使用

从 C 头文件生成 PHP 包装器：

```bash
c-to-php-ffi generate /path/to/header.h --output ./generated --namespace MyLib
```

### 指定库文件

生成包装器并指定共享库：

```bash
c-to-php-ffi generate /path/to/header.h \
    --output ./generated \
    --namespace MyLib \
    --library /path/to/libmylib.so
```

### 使用配置文件

创建配置文件 `config.yaml`：

```yaml
header_files:
  - /path/to/header1.h
  - /path/to/header2.h
library_file: /path/to/libmylib.so
output_path: ./generated
namespace: MyLib\FFI
validation:
  enable_parameter_validation: true
  enable_type_conversion: true
```

使用配置运行：

```bash
c-to-php-ffi generate --config config.yaml
```

## 配置

### CLI 选项

- `--output, -o`：生成文件的输出目录
- `--namespace, -n`：生成类的 PHP 命名空间
- `--library, -l`：共享库文件路径
- `--config, -c`：YAML 配置文件路径
- `--exclude`：排除生成的模式
- `--verbose, -v`：启用详细输出

### 配置文件格式

```yaml
# 要处理的头文件
header_files:
  - /path/to/header1.h
  - /path/to/header2.h

# 共享库文件
library_file: /path/to/library.so

# 输出配置
output_path: ./generated
namespace: MyProject\FFI

# 验证设置
validation:
  enable_parameter_validation: true
  enable_type_conversion: true
  custom_validation_rules: []

# 生成设置
generation:
  generate_documentation: true
  generate_examples: true
  include_phpdoc: true

# 排除模式
exclude_patterns:
  - "internal_*"
  - "_private_*"
```

## 生成代码结构

工具生成以下结构：

```
generated/
├── Classes/
│   ├── MathLibrary.php      # 主包装类
│   └── Structs/
│       └── Point.php        # 结构体包装类
├── Constants/
│   └── MathConstants.php    # 常量定义
├── Documentation/
│   ├── README.md           # 使用文档
│   └── Examples/
│       └── BasicUsage.php  # 使用示例
└── bootstrap.php           # 自动加载和初始化
```

## 使用示例

### 基本函数调用

```php
<?php
require_once 'generated/bootstrap.php';

use MyLib\MathLibrary;

$math = new MathLibrary();

// 使用自动验证调用 C 函数
$result = $math->add(5, 3); // 返回 8

// 使用结构体
$point = $math->createPoint(10.5, 20.3);
echo $point->x; // 10.5
```

### 错误处理

```php
<?php
use MyLib\MathLibrary;
use Yangweijie\CWrapper\Exception\ValidationException;

$math = new MathLibrary();

try {
    // 如果参数无效，这将抛出 ValidationException
    $result = $math->divide(10, 0);
} catch (ValidationException $e) {
    echo "验证错误: " . $e->getMessage();
}
```

## 开发

### 运行测试

```bash
# 运行所有测试
composer test

# 仅运行单元测试
composer test:unit

# 仅运行集成测试
composer test:integration
```

### 代码质量

```bash
# 检查代码风格
composer cs-check

# 修复代码风格
composer cs-fix

# 运行静态分析
composer phpstan

# 运行所有质量检查
composer quality
```

### 从源码构建

1. 克隆仓库：
```bash
git clone https://github.com/yangweijie/c-to-php-ffi-converter.git
cd c-to-php-ffi-converter
```

2. 安装依赖：
```bash
composer install
```

3. 运行测试：
```bash
composer test
```

4. 构建可执行文件：
```bash
chmod +x bin/c-to-php-ffi
```

## 故障排除

### 常见问题

**FFI 扩展未启用**
```
Error: FFI extension is not enabled
```
解决方案：在 php.ini 中启用 FFI 扩展：
```ini
extension=ffi
ffi.enable=true
```

**库未找到**
```
Error: Cannot load library: /path/to/lib.so
```
解决方案：确保库路径正确且库已为您的系统架构编译。

**头文件未找到**
```
Error: Cannot read header file: /path/to/header.h
```
解决方案：验证头文件路径并确保您有读取权限。

### 获取帮助

- 查看 [文档](docs/)
- 搜索 [现有问题](https://github.com/yangweijie/c-to-php-ffi-converter/issues)
- 创建 [新问题](https://github.com/yangweijie/c-to-php-ffi-converter/issues/new)

## 贡献

欢迎贡献！请阅读我们的 [贡献指南](CONTRIBUTING.md)，了解我们的行为准则和提交拉取请求的流程。

## 许可证

该项目基于 MIT 许可证 - 详情请见 [LICENSE](LICENSE) 文件。

## 致谢

- 基于 [klitsche/ffigen](https://github.com/klitsche/ffigen) 构建
- 使用 [Symfony Console](https://symfony.com/doc/current/components/console.html) 作为 CLI 接口
- 模板引擎由 [Twig](https://twig.symfony.com/) 提供支持

## 更新日志

请参见 [CHANGELOG.md](CHANGELOG.md) 获取更改列表和版本历史。