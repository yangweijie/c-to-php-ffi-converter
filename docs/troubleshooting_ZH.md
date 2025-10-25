# 故障排除指南

本指南涵盖了使用 C-to-PHP FFI 转换器时的常见问题及其解决方案。

## 安装问题

### FFI 扩展不可用

**问题**: `Fatal error: Uncaught Error: Class 'FFI' not found`

**原因**: FFI 扩展未安装或未启用。

**解决方案**:

1. **检查 FFI 是否已安装**:
```bash
php -m | grep -i ffi
```

2. **安装 FFI 扩展** (如果未安装):
```bash
# Ubuntu/Debian
sudo apt install php-ffi

# CentOS/RHEL
sudo yum install php-ffi

# macOS with Homebrew
brew install php --with-ffi
```

3. **在 php.ini 中启用 FFI**:
```ini
extension=ffi
ffi.enable=true
```

4. **重启 Web 服务器** (如果适用):
```bash
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

### Composer 自动加载器未找到

**问题**: `Could not find autoloader. Please run 'composer install'.`

**原因**: 依赖项未安装或自动加载器缺失。

**解决方案**:

1. **安装依赖项**:
```bash
composer install
```

2. **检查安装方法**:
```bash
# 全局安装
composer global require yangweijie/c-to-php-ffi-converter

# 项目内安装
composer require --dev yangweijie/c-to-php-ffi-converter
```

3. **验证 PATH** (全局安装):
```bash
echo $PATH | grep composer
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

## 库加载问题

### 共享库未找到

**问题**: `Cannot load library: /path/to/library.so`

**原因**: 共享库文件不存在或不可访问。

**解决方案**:

1. **验证库是否存在**:
```bash
ls -la /path/to/library.so
```

2. **检查库依赖项**:
```bash
ldd /path/to/library.so
```

3. **设置库路径**:
```bash
export LD_LIBRARY_PATH=/path/to/library/directory:$LD_LIBRARY_PATH
```

4. **使用绝对路径**:
```bash
c-to-php-ffi generate header.h --library /absolute/path/to/library.so
```

### 架构不匹配

**问题**: `library.so: wrong ELF class: ELFCLASS32`

**原因**: 库架构与 PHP 架构不匹配（32位 vs 64位）。

**解决方案**:

1. **检查 PHP 架构**:
```bash
php -r "echo PHP_INT_SIZE * 8; echo ' bit PHP';"
```

2. **重新编译库** 以适应正确的架构:
```bash
# 对于 64 位
gcc -m64 -shared -fPIC -o library.so source.c

# 对于 32 位
gcc -m32 -shared -fPIC -o library.so source.c
```

## 头文件问题

### 头文件未找到

**问题**: `Cannot read header file: /path/to/header.h`

**原因**: 头文件不存在或不可读。

**解决方案**:

1. **验证文件是否存在**:
```bash
ls -la /path/to/header.h
```

2. **检查权限**:
```bash
chmod 644 /path/to/header.h
```

3. **使用绝对路径**:
```bash
c-to-php-ffi generate /absolute/path/to/header.h
```

### 包含依赖项缺失

**问题**: `fatal error: 'dependency.h' file not found`

**原因**: 头文件包含的其他头文件找不到。

**解决方案**:

1. **安装开发包**:
```bash
# Ubuntu/Debian
sudo apt install build-essential libc6-dev

# CentOS/RHEL
sudo yum groupinstall "Development Tools"
```

2. **添加包含路径** 到您的 C 编译:
```bash
gcc -I/usr/include -I/usr/local/include -shared -fPIC -o library.so source.c
```

3. **复制缺失的头文件** 到您的项目目录。

## 生成问题

### 内存耗尽

**问题**: `Fatal error: Allowed memory size exhausted`

**原因**: 大型头文件或复杂项目超出 PHP 内存限制。

**解决方案**:

1. **增加内存限制**:
```bash
php -d memory_limit=1G c-to-php-ffi generate header.h
```

2. **将大型头文件拆分** 为较小的文件。

3. **使用排除模式**:
```yaml
exclude_patterns:
  - "internal_*"
  - "_private_*"
```

### 权限被拒绝

**问题**: `Permission denied: cannot write to output directory`

**原因**: 写入输出目录的权限不足。

**解决方案**:

1. **检查目录权限**:
```bash
ls -la /path/to/output/directory
```

2. **创建具有适当权限的目录**:
```bash
mkdir -p /path/to/output
chmod 755 /path/to/output
```

3. **更改所有权** (如果需要):
```bash
sudo chown $USER:$USER /path/to/output
```

## 运行时问题

### 验证错误

**问题**: `ValidationException: Parameter type mismatch`

**原因**: 生成的包装器正在严格验证参数。

**解决方案**:

1. **检查参数类型**:
```php
// 确保正确的类型
$result = $math->add(5, 3);        // int, int ✓
$result = $math->add("5", 3);      // string, int ✗
```

2. **禁用验证** (如果需要):
```yaml
validation:
  enable_parameter_validation: false
```

3. **使用类型转换**:
```yaml
validation:
  enable_type_conversion: true
```

### 段错误

**问题**: `Segmentation fault (core dumped)`

**原因**: C 库中的内存访问违规或 FFI 使用不正确。

**解决方案**:

1. **启用调试**:
```bash
DEBUG=1 c-to-php-ffi generate header.h
```

2. **独立测试 C 库**:
```c
// 创建测试程序
#include "library.h"
int main() {
    // 在此处测试函数
    return 0;
}
```

3. **检查指针处理**:
```php
// 确保正确的指针使用
$ptr = $lib->createPointer();
// 使用 $ptr...
$lib->freePointer($ptr); // 如果需要，进行清理
```

## 配置问题

### YAML 解析错误

**问题**: `Unable to parse YAML configuration`

**原因**: 配置文件中的 YAML 语法无效。

**解决方案**:

1. **验证 YAML 语法**:
```bash
php -r "yaml_parse_file('config.yaml');"
```

2. **检查缩进** (使用空格，不要使用制表符):
```yaml
header_files:
  - header1.h  # 2 个空格
  - header2.h  # 2 个空格
```

3. **引用特殊字符**:
```yaml
library_file: "/path/with spaces/library.so"
```

### 配置未找到

**问题**: `Configuration file not found: config.yaml`

**原因**: 配置文件不存在或路径不正确。

**解决方案**:

1. **使用绝对路径**:
```bash
c-to-php-ffi generate --config /absolute/path/to/config.yaml
```

2. **检查当前目录**:
```bash
ls -la config.yaml
```

3. **创建默认配置**:
```bash
c-to-php-ffi init-config > config.yaml
```

## 性能问题

### 生成缓慢

**问题**: 生成过程耗时很长。

**原因**: 大型头文件或复杂的依赖解析。

**解决方案**:

1. **使用排除模式**:
```yaml
exclude_patterns:
  - "test_*"
  - "*_internal"
```

2. **限制头文件**:
```yaml
header_files:
  - essential_header.h
  # 注释掉非必要的头文件
  # - optional_header.h
```

3. **增加内存限制**:
```bash
php -d memory_limit=2G c-to-php-ffi generate header.h
```

## 平台特定问题

### Windows 问题

**问题**: 各种 Windows 特定错误。

**解决方案**:

1. **使用 Windows 路径**:
```bash
c-to-php-ffi generate C:\path\to\header.h --library C:\path\to\library.dll
```

2. **安装 Visual C++ Redistributable**。

3. **使用 WSL** 获得类似 Linux 的环境:
```bash
wsl --install
```

### macOS 问题

**问题**: macOS 上的库加载问题。

**解决方案**:

1. **设置 DYLD_LIBRARY_PATH**:
```bash
export DYLD_LIBRARY_PATH=/path/to/library:$DYLD_LIBRARY_PATH
```

2. **使用 .dylib 扩展名**:
```bash
gcc -shared -o library.dylib source.c
```

3. **安装 Xcode Command Line Tools**:
```bash
xcode-select --install
```

## 获取更多帮助

### 调试信息

启用调试模式以获取更多信息:

```bash
DEBUG=1 c-to-php-ffi generate header.h --verbose
```

### 日志文件

检查日志文件（如果已配置）:

```bash
tail -f /tmp/c-to-php-ffi.log
```

### 系统信息

收集系统信息以报告错误:

```bash
php --version
php -m | grep -i ffi
composer --version
uname -a
```

### 报告问题

报告问题时，请包含:

1. **完整的错误信息**
2. **系统信息**（操作系统、PHP 版本等）
3. **重现步骤**
4. **示例头文件**（如果可能）
5. **使用的配置**

在以下地址创建问题: https://github.com/yangweijie/c-to-php-ffi-converter/issues

### 社区支持

- **GitHub Discussions**: https://github.com/yangweijie/c-to-php-ffi-converter/discussions
- **Stack Overflow**: 使用 `c-to-php-ffi-converter` 标签标记问题
- **PHP FFI 文档**: https://www.php.net/manual/en/book.ffi.php

## 常见问题

### Q: 我可以将此工具与 C++ 库一起使用吗？

A: 该工具专为 C 库设计。对于 C++，您需要创建 C 包装函数或使用 extern "C" 声明。

### Q: 这是否适用于所有 C 库？

A: 大多数标准 C 库都能很好地工作。大量使用函数指针或回调的复杂库可能需要手动调整。

### Q: 如何处理回调？

A: 回调需要手动实现。该工具生成基本包装器，但回调处理需要自定义代码。

### Q: 我可以自定义生成的代码吗？

A: 是的，您可以修改 Twig 模板或对生成的文件进行后处理。

### Q: 这是否可用于生产环境？

A: 该工具生成可用于生产的包装器，但始终要针对您的特定用例进行彻底测试。

[English Guide](troubleshooting.md)