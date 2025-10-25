# IFLOW.md - C-to-PHP FFI Converter 上下文

## 1. 项目概览

这是一个用于将 C 项目自动转换为面向对象 PHP FFI 包装类的强大工具。它基于 [klitsche/ffigen](https://github.com/klitsche/ffigen) 构建，提供了增强功能，包括参数验证、错误处理和全面的文档生成。

### 核心特性

- **自动包装器生成**：将 C 头文件转换为 PHP FFI 包装器类
- **参数验证**：C 函数参数的运行时类型检查和验证
- **错误处理**：带有描述性异常的全面错误处理
- **文档生成**：自动生成 PHPDoc 注释和使用示例
- **CLI 接口**：易于使用的命令行界面
- **灵活配置**：支持 YAML 配置文件和 CLI 选项
- **依赖解析**：自动处理头文件依赖关系

### 技术栈

- **语言**：PHP 8.1+
- **核心依赖**：
  - `klitsche/ffigen`：用于生成 FFI 绑定
  - `symfony/console`：CLI 命令行界面
  - `symfony/yaml`：YAML 配置解析
  - `twig/twig`：模板引擎
  - `phpstan/phpdoc-parser`：PHPDoc 解析
  - `psr/log`：日志接口
- **开发依赖**：
  - `phpunit/phpunit`：单元测试框架
  - `mockery/mockery`：Mock 测试库
  - `mikey179/vfsstream`：虚拟文件系统
  - `phpstan/phpstan`：静态分析工具
  - `squizlabs/php_codesniffer`：代码风格检查

## 2. 构建和运行

### 环境要求

- PHP 8.1 或更高版本
- 启用 PHP FFI 扩展
- Composer
- C 编译器（用于编译示例库）

### 安装

#### 全局安装（推荐）

```bash
composer global require yangweijie/c-to-php-ffi-converter
```

确保全局 Composer bin 目录在你的 PATH 中：

```bash
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

#### 项目内安装

```bash
composer require --dev yangweijie/c-to-php-ffi-converter
```

### 基本使用

#### 从 C 头文件生成包装器

```bash
c-to-php-ffi generate /path/to/header.h --output ./generated --namespace MyLib
```

#### 指定共享库

```bash
c-to-php-ffi generate /path/to/header.h \
    --output ./generated \
    --namespace MyLib \
    --library /path/to/libmylib.so
```

#### 使用配置文件

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

使用配置文件运行：

```bash
c-to-php-ffi generate --config config.yaml
```

### 开发

#### 运行测试

```bash
# 运行所有测试
composer test

# 仅运行单元测试
composer test:unit

# 仅运行集成测试
composer test:integration
```

#### 代码质量检查

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

#### 从源码构建

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

## 3. 开发约定

### 代码结构

```
src/
├── Analyzer/         # C 代码分析器
├── Config/           # 配置加载和验证
├── Console/          # CLI 命令行接口
├── Documentation/    # 文档生成器
├── Exception/        # 自定义异常类
├── Generator/        # PHP 包装器生成器
├── Integration/      # 与 klitsche/ffigen 集成
├── Logging/          # 日志记录
└── Validation/       # 参数验证
```

### 命名约定

- 类名使用 PascalCase
- 方法名使用 camelCase
- 常量使用 UPPER_SNAKE_CASE
- 遵循 PSR-4 自动加载标准

### 代码质量

- 遵循 PSR-12 代码风格
- 所有公共方法必须有 PHPDoc 注释
- 使用类型声明（参数和返回值）
- 保持方法简洁，单一职责原则
- 编写单元测试覆盖核心功能

### 测试

- 单元测试位于 `tests/Unit`
- 集成测试位于 `tests/Integration`
- 使用 PHPUnit 作为测试框架
- 使用 Mockery 进行 Mock 测试
- 测试覆盖率目标为 80% 以上

### 异常处理

- 使用自定义异常类继承 `FFIConverterException`
- 提供详细的错误上下文和调试信息
- 区分可恢复和不可恢复错误
- 提供修复建议

### 配置

- 支持 YAML 配置文件
- 支持 CLI 参数覆盖配置
- 配置验证使用 `ConfigValidator`
- 敏感配置不应硬编码

## 4. CLI 命令

### generate

生成 PHP FFI 包装器类的主要命令。

```bash
c-to-php-ffi generate [options] [--] [<header-files>...]
```

#### 参数

- `header-files`：要处理的 C 头文件路径（可选，多个）

#### 选项

- `--output, -o`：生成文件的输出目录（默认：`./generated`）
- `--namespace`：生成类的 PHP 命名空间（默认：`Generated\FFI`）
- `--library, -l`：共享库文件路径（.so, .dll, .dylib）
- `--config, -c`：配置文件路径（YAML 格式）
- `--exclude`：排除模式（可多次使用）
- `--validation`：在生成的包装器中启用参数验证
- `--force, -f`：覆盖现有文件而不确认
- `--help, -h`：显示帮助信息
- `--quiet, -q`：不输出任何消息
- `--verbose, -v`：增加详细程度
- `--version, -V`：显示应用程序版本

## 5. 配置文件格式

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

## 6. 生成代码结构

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