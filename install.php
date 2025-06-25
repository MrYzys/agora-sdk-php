<?php

/**
 * Agora RTC SDK 安装和配置脚本
 */

echo "=== Agora RTC SDK for PHP 安装向导 ===\n\n";

// 检查PHP版本
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    echo "❌ 错误: 需要PHP 7.4或更高版本，当前版本: " . PHP_VERSION . "\n";
    exit(1);
}
echo "✅ PHP版本检查通过: " . PHP_VERSION . "\n";

// 检查必需的扩展
$requiredExtensions = ['curl', 'json', 'hash'];
$missingExtensions = [];

foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingExtensions[] = $extension;
    }
}

if (!empty($missingExtensions)) {
    echo "❌ 错误: 缺少必需的PHP扩展: " . implode(', ', $missingExtensions) . "\n";
    exit(1);
}
echo "✅ PHP扩展检查通过\n";

// 检查Composer
if (!file_exists('vendor/autoload.php')) {
    echo "⚠️  警告: 未找到vendor/autoload.php，请先运行 'composer install'\n";
}

// 创建配置文件
echo "\n=== 配置文件设置 ===\n";

if (!file_exists('config/agora.php')) {
    if (file_exists('config/agora.example.php')) {
        copy('config/agora.example.php', 'config/agora.php');
        echo "✅ 已创建配置文件: config/agora.php\n";
        echo "📝 请编辑 config/agora.php 文件，填入您的Agora配置信息\n";
    } else {
        echo "⚠️  警告: 未找到配置示例文件\n";
    }
} else {
    echo "ℹ️  配置文件已存在: config/agora.php\n";
}

// 创建日志目录
$logDir = 'logs';
if (!is_dir($logDir)) {
    if (mkdir($logDir, 0755, true)) {
        echo "✅ 已创建日志目录: {$logDir}\n";
    } else {
        echo "⚠️  警告: 无法创建日志目录: {$logDir}\n";
    }
}

// 检查目录权限
$writableDirs = ['logs'];
foreach ($writableDirs as $dir) {
    if (is_dir($dir) && !is_writable($dir)) {
        echo "⚠️  警告: 目录不可写: {$dir}\n";
    }
}

echo "\n=== 配置向导 ===\n";

// 交互式配置（如果在命令行环境）
if (php_sapi_name() === 'cli') {
    $interactive = readline("是否要进行交互式配置? (y/n): ");
    
    if (strtolower(trim($interactive)) === 'y') {
        echo "\n请输入您的Agora配置信息:\n";
        
        $appId = readline("App ID: ");
        $appCertificate = readline("App Certificate: ");
        $customerId = readline("Customer ID (可选，用于云录制): ");
        $customerSecret = readline("Customer Secret (可选，用于云录制): ");
        
        // 生成配置文件内容
        $configContent = generateConfigFile($appId, $appCertificate, $customerId, $customerSecret);
        
        if (file_put_contents('config/agora.php', $configContent)) {
            echo "✅ 配置文件已更新\n";
        } else {
            echo "❌ 配置文件更新失败\n";
        }
    }
}

echo "\n=== 测试连接 ===\n";

// 如果配置文件存在，尝试测试基本功能
if (file_exists('config/agora.php') && file_exists('vendor/autoload.php')) {
    try {
        require_once 'vendor/autoload.php';
        
        $config = require 'config/agora.php';
        
        if (!empty($config['app_id']) && !empty($config['app_certificate'])) {
            $sdk = \Agora\Sdk\AgoraSdk::create(
                $config['app_id'],
                $config['app_certificate']
            );
            
            // 测试Token生成
            $testToken = $sdk->generateToken('test_channel', 12345);
            
            if (!empty($testToken)) {
                echo "✅ Token生成测试通过\n";
            } else {
                echo "❌ Token生成测试失败\n";
            }
        } else {
            echo "⚠️  跳过测试: 配置信息不完整\n";
        }
    } catch (Exception $e) {
        echo "❌ 测试失败: " . $e->getMessage() . "\n";
    }
}

echo "\n=== 安装完成 ===\n";
echo "📚 查看文档: README.md\n";
echo "🔧 示例代码: examples/\n";
echo "🧪 运行测试: composer test\n";
echo "\n下一步:\n";
echo "1. 编辑 config/agora.php 配置文件\n";
echo "2. 查看 examples/ 目录中的示例代码\n";
echo "3. 运行测试确保一切正常\n";

/**
 * 生成配置文件内容
 */
function generateConfigFile($appId, $appCertificate, $customerId = '', $customerSecret = '')
{
    $template = file_get_contents('config/agora.example.php');
    
    $replacements = [
        'your_app_id_here' => $appId,
        'your_app_certificate_here' => $appCertificate,
        'your_customer_id_here' => $customerId,
        'your_customer_secret_here' => $customerSecret,
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $template);
}
