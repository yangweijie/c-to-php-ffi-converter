# 常见问题解答 (FAQ)

## 一般问题

### Q: 什么是 C-to-PHP FFI 转换器？

A: C-to-PHP FFI 转换器是一个工具，可自动从 C 库生成面向对象的 PHP 包装类。它构建在 klitsche/ffigen 之上，提供增强功能，包括参数验证、错误处理和全面的文档生成。

### Q: 这与 klitsche/ffigen 有什么不同？

A: 虽然 klitsche/ffigen 生成低级 FFI 绑定（constants.php 和 Methods.php 特性），但我们的工具创建完整的面向对象包装类，具有：
- 参数验证和类型检查
- 全面的错误处理
- 自动文档生成
- 用户友好的 CLI 界面
- 配置管理
- 更好的集成模式

### Q: 支持哪些 C 库？

A: 该工具适用于大多数标准 C 库，这些库：
- 具有格式良好的头文件
- 使用标准 C 数据类型
- 不严重依赖复杂的宏或预处理器魔法
- 编译为共享库（.so、.dll、.dylib）

## 安装和设置

### Q: 系统要求是什么？

A: 您需要：
- PHP 8.1 或更高版本
- 启用 FFI 扩展
- Composer
- C 编译器（用于构建示例库）
- 作为共享库编译的目标 C 库

### Q: 如何启用 FFI 扩展？

A: 在 php.ini 中添加这些行：
```ini
extension=ffi
ffi.enable=true
```
然后重启 Web 服务器或 PHP-FPM（如果适用）。

### Q: 我可以在生产环境中使用吗？

A: 是的，生成的包装器可用于生产。但是，始终：
- 使用您的特定用例进行彻底测试
- 在开发期间启用参数验证
- 适当处理异常
- 监控大数据集的内存使用情况

### Q: 这在 Windows 上可用吗？

A: 是的，该工具在 Windows、Linux 和 macOS 上都可用。在 Windows 上：
- 使用 .dll 文件而不是 .so 文件
- 确保安装了 Visual C++ Redistributable
- 考虑使用 WSL 获得类似 Linux 的环境

## 使用问题

### Q: 如何处理 C 结构体？

A: C 结构体会自动转换为 PHP 类：

```c
// C 结构体
typedef struct {
    int x;
    int y;
} Point;
```

```php
// 生成的 PHP 类
$point = new Point();
$point->x = 10;
$point->y = 20;
```

### Q: 函数指针和回调如何处理？

A: 函数指针需要手动处理。该工具生成基本包装器，但回调实现需要自定义代码。考虑创建接受简单参数而不是函数指针的 C 包装函数。

### Q: 如何处理内存管理？

A: 该工具为大多数情况提供自动内存管理：
- 简单返回值会自动处理
- 字符串返回值会转换为 PHP 字符串
- 对于复杂的内存模式，您可能需要手动管理

### Q: 我可以自定义生成的代码吗？

A: 是的，您可以：
- 修改生成器中的 Twig 模板
- 对生成的文件进行后处理
- 使用配置选项控制生成
- 在您自己的代码中扩展生成的类

### Q: 如何处理大型 C 库？

A: 对于大型库：
- 使用排除模式跳过不必要的函数
- 将生成拆分为多个较小的库
- 增加生成期间的 PHP 内存限制
- 考虑仅生成您需要的函数

## 配置问题

### Q: 有哪些配置选项？

A: 主要选项包括：
- `header_files`：要处理的 C 头文件
- `library_file`：共享库路径
- `output_path`：生成 PHP 文件的位置
- `namespace`：生成类的 PHP 命名空间
- `validation`：参数验证设置
- `exclude_patterns`：要跳过的函数/类型

### Q: 如何排除某些函数？

A: 在配置中使用排除模式：
```yaml
exclude_patterns:
  - "internal_*"
  - "_private_*"
  - "test_*"
```

### Q: 我可以使用多个头文件吗？

A: 是的，在配置中列出它们：
```yaml
header_files:
  - header1.h
  - header2.h
  - subdir/header3.h
```

### Q: 如何处理头文件依赖关系？

A: 该工具自动解析依赖关系。确保：
- 所有必需的头文件都可访问
- 系统头文件已安装
- 包含路径正确

## 错误处理

### Q: 如果生成失败该怎么办？

A: 检查：
- 有效的头文件语法
- 可访问的库文件
- 正确的文件权限
- 充足的内存（使用 `-d memory_limit=1G` 增加）
- 缺少的依赖项

### Q: 如何调试 FFI 问题？

A: 启用调试模式：
```bash
DEBUG=1 c-to-php-ffi generate header.h --verbose
```

还请检查：
- FFI 扩展是否已加载（`php -m | grep ffi`）
- 库是否可以加载（`ldd library.so`）
- 正确的架构（32位 vs 64位）

### Q: "符号未找到" 错误如何处理？

A: 这通常意味着：
- 库未编译该函数
- 函数名称修饰（使用 `nm -D library.so` 检查符号）
- 缺少库依赖项
- 库路径不正确

## 性能问题

### Q: 生成的包装器速度如何？

A: 性能取决于：
- FFI 开销（对于计算任务通常表现良好）
- 参数验证（可以禁用）
- 函数调用频率
- 数据传输大小

对于高性能场景，考虑在生产中禁用验证。

### Q: 我可以优化生成的代码吗？

A: 是的：
- 在生产中禁用参数验证
- 谨慎使用类型转换
- 缓存库实例
- 最小化 PHP 和 C 之间的数据复制

### Q: 这使用多少内存？

A: 内存使用量取决于：
- C 库的大小
- 处理的数据量
- 并发操作的数量
- PHP 内存管理

使用 `memory_get_usage()` 监控，并根据需要调整 `memory_limit`。

## 高级用法

### Q: 我可以扩展生成的类吗？

A: 是的，您可以扩展或组合生成的类：
```php
class MyMathLibrary extends GeneratedMathLibrary {
    public function advancedFunction($param) {
        // 您的自定义逻辑
        return $this->basicFunction($param);
    }
}
```

### Q: 如何处理 C 库中的版本差异？

A: 对不同版本使用不同配置：
```yaml
# config-v1.yaml
library_file: ./libmath-v1.so
namespace: MyLib\V1

# config-v2.yaml  
library_file: ./libmath-v2.so
namespace: MyLib\V2
```

### Q: 我可以将此工具与 C++ 库一起使用吗？

A: 不能直接使用。对于 C++ 库：
- 使用 `extern "C"` 创建 C 包装函数
- 在接口中仅使用 C 兼容类型
- 在包装层处理 C++ 异常

### Q: 如何处理线程？

A: FFI 和线程注意事项：
- PHP FFI 默认不是线程安全的
- 避免在线程之间共享 FFI 实例
- 使用基于进程的并行性而不是线程
- 考虑使用单独的进程进行并发访问

## 故障排除

### Q: 为什么我收到 "Class 'FFI' not found"？

A: FFI 扩展未启用。在 php.ini 中添加：
```ini
extension=ffi
ffi.enable=true
```

### Q: 为什么生成过程非常缓慢？

A: 常见原因：
- 包含许多包含项的大型头文件
- 复杂的依赖解析
- 内存不足
- 磁盘 I/O 缓慢

尝试增加内存限制并使用排除模式。

### Q: 如何报告错误？

A: 报告问题时，请包含：
- 完整的错误信息
- 系统信息（操作系统、PHP 版本）
- 示例头文件（如果可能）
- 使用的配置
- 重现步骤

在以下地址创建问题：https://github.com/yangweijie/c-to-php-ffi-converter/issues

## 最佳实践

### Q: 有哪些推荐实践？

A: 遵循以下指南：
- 在开发期间启用验证
- 使用描述性命名空间
- 彻底测试生成的包装器
- 适当处理异常
- 记录任何手动修改
- 保持 C 接口简单
- 对生成的代码使用版本控制

### Q: 我应该如何构建项目？

A: 推荐结构：
```
project/
├── c-library/          # C 源代码
├── config/             # 转换器配置
├── generated/          # 生成的 PHP 包装器
├── src/               # 您的 PHP 应用程序代码
└── tests/             # C 和 PHP 代码的测试
```

### Q: 我应该提交生成的代码吗？

A: 这取决于您的工作流程：
- **提交**：对于稳定的 API，更容易部署
- **不提交**：对于频繁变化的 API，存储库更干净

如果不提交，请确保您的构建过程重新生成包装器。

## 获取帮助

### Q: 在哪里可以获得更多信息？

A: 可用资源：
- [文档](README_ZH.md) - 全面指南
- [示例](examples/) - 工作代码示例
- [GitHub Issues](https://github.com/yangweijie/c-to-php-ffi-converter/issues) - 错误报告和功能请求
- [GitHub Discussions](https://github.com/yangweijie/c-to-php-ffi-converter/discussions) - 一般问题
- [故障排除指南](troubleshooting_ZH.md) - 常见问题和解决方案

### Q: 如何贡献？

A: 欢迎贡献：
- 报告错误和建议功能
- 提交拉取请求
- 改进文档
- 添加示例
- 帮助其他用户

有关详细信息，请参见 [CONTRIBUTING.md](../CONTRIBUTING.md)。

### Q: 有社区吗？

A: 加入社区：
- GitHub Discussions 用于问题和想法
- Issues 用于错误报告和功能请求
- 带有 `c-to-php-ffi-converter` 标签的 Stack Overflow
- 关注项目获取更新

---

*在这里没有看到您的问题？请查看 [故障排除指南](troubleshooting_ZH.md) 或在 [GitHub Discussions](https://github.com/yangweijie/c-to-php-ffi-converter/discussions) 中提问。*

[English FAQ](faq.md)