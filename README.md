# Agora RTC Server SDK for PHP

声网（Agora）RTC服务端SDK，基于最新的Agora接口文档封装，提供完整的RTC房间管理、Token生成、事件回调解析和云录制功能。

## 功能特性

- ✅ **RTC房间创建** - 频道管理和用户Token生成
- ✅ **客户端Token生成** - 基于AccessToken2的安全认证
- ✅ **事件回调解析** - Webhook事件解析和签名验证
- ✅ **云录制管理** - 完整的录制生命周期管理
- ✅ **类型安全** - 完整的类型提示和异常处理
- ✅ **易于使用** - 简洁的API设计

## 安装

使用Composer安装：

```bash
composer require agora/rtc-server-sdk
```

## 快速开始

### 1. 初始化SDK

```php
<?php

require_once 'vendor/autoload.php';

use Agora\RtcSdk\AgoraRtcSdk;

// 基础配置（仅Token生成）
$sdk = AgoraRtcSdk::create(
    'your_app_id',
    'your_app_certificate'
);

// 完整配置（包含RESTful API功能）
$sdk = AgoraRtcSdk::create(
    'your_app_id',
    'your_app_certificate',
    'your_customer_id',      // 用于RESTful API认证
    'your_customer_secret'   // 用于RESTful API认证
);
```

### 2. 房间创建和Token生成

```php
// 创建房间（逻辑概念）
$room = $sdk->createRoom('test_channel', [
    'admin_uid' => 1,
    'token_expire_time' => 3600, // 1小时
]);

echo "房间创建成功：" . $room['channel_name'] . "\n";
echo "管理员Token：" . $room['admin_token'] . "\n";

// 为用户生成Token
$userToken = $sdk->generateUserToken(
    'test_channel',
    12345,      // 用户ID
    true,       // 是否为发布者
    3600        // 过期时间（秒）
);

echo "用户Token：" . $userToken['token'] . "\n";
```

### 3. 云录制

```php
// 配置云存储
use Agora\RtcSdk\CloudRecording\CloudRecordingClient;

$storageConfig = CloudRecordingClient::createStorageConfig(
    CloudRecordingClient::VENDOR_AMAZON_S3,  // 云存储厂商
    0,                                        // 区域
    'your-bucket-name',                       // 存储桶
    'your-access-key',                        // 访问密钥
    'your-secret-key'                         // 密钥
);

// 开始录制
$recording = $sdk->startRecording('test_channel', $storageConfig, [
    'recording_uid' => 999999,
    'mode' => CloudRecordingClient::MODE_COMPOSITE,
    'recording_config' => [
        'channelType' => CloudRecordingClient::CHANNEL_TYPE_COMMUNICATION,
        'streamTypes' => 2, // 录制音视频
        'maxIdleTime' => 30,
    ],
]);

echo "录制开始，SID：" . $recording['sid'] . "\n";

// 查询录制状态
$status = $sdk->queryRecording(
    $recording['resource_id'],
    $recording['sid']
);

echo "录制状态：" . $status['status'] . "\n";

// 停止录制
$result = $sdk->stopRecording(
    $recording['resource_id'],
    $recording['sid'],
    'test_channel',
    999999
);

echo "录制已停止\n";
```

### 4. Webhook事件解析

```php
// 解析Webhook事件
$requestBody = file_get_contents('php://input');
$headers = getallheaders();

try {
    $event = $sdk->parseWebhookEvent($requestBody, $headers, true);
    
    echo "事件类型：" . $event['event_name'] . "\n";
    echo "频道名称：" . $event['payload']['channelName'] . "\n";
    
    // 根据事件类型处理
    switch ($event['event_type']) {
        case 101: // 频道创建
            echo "频道已创建\n";
            break;
        case 103: // 主播加入
            echo "主播 " . $event['payload']['uid'] . " 加入频道\n";
            break;
        case 104: // 主播离开
            echo "主播 " . $event['payload']['uid'] . " 离开频道\n";
            break;
    }
} catch (Exception $e) {
    echo "事件解析失败：" . $e->getMessage() . "\n";
}
```

## 详细文档

### Token生成

#### 基础Token生成

```php
// 生成发布者Token
$token = $sdk->generateToken(
    'channel_name',
    12345,                                    // 用户ID
    \Agora\RtcSdk\TokenBuilder\RtcTokenBuilder2::ROLE_PUBLISHER,
    3600                                      // 过期时间（秒）
);

// 生成订阅者Token
$token = $sdk->generateToken(
    'channel_name',
    12346,
    \Agora\RtcSdk\TokenBuilder\RtcTokenBuilder2::ROLE_SUBSCRIBER,
    3600
);
```

#### 详细权限Token生成

```php
$token = $sdk->generateTokenWithPrivileges(
    'channel_name',
    12345,
    3600,    // Token过期时间
    3600,    // 加入频道权限过期时间
    3600,    // 发布音频权限过期时间
    3600,    // 发布视频权限过期时间
    3600     // 发布数据流权限过期时间
);
```

### 云录制配置

#### 存储配置

```php
use Agora\RtcSdk\CloudRecording\CloudRecordingClient;

// Amazon S3
$storageConfig = CloudRecordingClient::createStorageConfig(
    CloudRecordingClient::VENDOR_AMAZON_S3,
    0,  // 区域：0=US_EAST_1, 1=US_EAST_2, 2=US_WEST_1, 3=US_WEST_2
    'bucket-name',
    'access-key',
    'secret-key'
);

// 阿里云OSS
$storageConfig = CloudRecordingClient::createStorageConfig(
    CloudRecordingClient::VENDOR_ALIBABA_CLOUD,
    0,  // 区域：0=CN_Hangzhou, 1=CN_Shanghai, 2=CN_Qingdao
    'bucket-name',
    'access-key',
    'secret-key'
);

// 腾讯云COS
$storageConfig = CloudRecordingClient::createStorageConfig(
    CloudRecordingClient::VENDOR_TENCENT_CLOUD,
    0,  // 区域：0=AP_Beijing_1, 1=AP_Beijing, 2=AP_Shanghai
    'bucket-name',
    'access-key',
    'secret-key'
);
```

#### 录制配置

```php
$recordingConfig = [
    'channelType' => CloudRecordingClient::CHANNEL_TYPE_COMMUNICATION, // 0=通信，1=直播
    'streamTypes' => 2,        // 0=仅音频，1=仅视频，2=音视频
    'maxIdleTime' => 30,       // 最大空闲时间（秒）
    'subscribeVideoUids' => ['123', '456'], // 订阅的视频用户ID列表
    'subscribeAudioUids' => ['123', '456'], // 订阅的音频用户ID列表
];
```

### 事件类型

| 事件代码 | 事件名称 | 描述 |
|---------|---------|------|
| 101 | channel_create | 频道创建 |
| 102 | channel_destroy | 频道销毁 |
| 103 | broadcaster_join_channel | 主播加入频道 |
| 104 | broadcaster_leave_channel | 主播离开频道 |
| 105 | audience_join_channel | 观众加入频道 |
| 106 | audience_leave_channel | 观众离开频道 |
| 111 | client_role_change_to_broadcaster | 观众变为主播 |
| 112 | client_role_change_to_audience | 主播变为观众 |

## 错误处理

```php
use Agora\RtcSdk\Exceptions\AgoraException;

try {
    $token = $sdk->generateToken('test_channel', 12345);
} catch (AgoraException $e) {
    echo "错误类型：" . $e->getErrorCode() . "\n";
    echo "错误消息：" . $e->getMessage() . "\n";
    echo "HTTP状态码：" . $e->getCode() . "\n";
    
    // 获取详细错误信息
    $details = $e->getErrorDetails();
    if (!empty($details)) {
        echo "错误详情：" . json_encode($details) . "\n";
    }
}
```

## 配置文件

您可以使用配置文件来管理Agora设置：

```php
// 复制配置示例文件
cp config/agora.example.php config/agora.php

// 编辑配置文件
$config = require 'config/agora.php';

$sdk = AgoraRtcSdk::create(
    $config['app_id'],
    $config['app_certificate'],
    $config['customer_id'],
    $config['customer_secret']
);
```

## 运行测试

```bash
# 安装依赖
composer install

# 运行测试
composer test

# 运行代码风格检查
composer cs-check

# 修复代码风格
composer cs-fix
```

## 常见问题

### 1. Token生成失败

**问题**: Token生成时出现错误
**解决方案**:
- 检查App ID和App Certificate是否正确
- 确保频道名称格式正确（长度不超过64字符，只包含允许的字符）
- 检查用户ID是否为正整数

### 2. 云录制启动失败

**问题**: 录制无法启动
**解决方案**:
- 确保Customer ID和Customer Secret配置正确
- 检查存储配置（bucket、accessKey、secretKey）
- 确保Token有效且未过期
- 检查频道中是否有用户

### 3. Webhook签名验证失败

**问题**: Webhook事件解析时签名验证失败
**解决方案**:
- 确保Customer Secret正确
- 检查请求头中是否包含正确的签名
- 确认使用的签名算法（SHA1或SHA256）

### 4. 404错误

**问题**: 调用录制查询API时返回404
**解决方案**:
- 检查资源ID和录制ID是否正确
- 确认录制是否已经开始
- 检查录制是否已经结束

## API参考

### AgoraRtcSdk类

#### 主要方法

- `create()` - 创建SDK实例
- `generateToken()` - 生成RTC Token
- `generateUserToken()` - 为用户生成Token
- `createRoom()` - 创建房间
- `startRecording()` - 开始录制
- `stopRecording()` - 停止录制
- `queryRecording()` - 查询录制状态
- `parseWebhookEvent()` - 解析Webhook事件

### CloudRecordingClient类

#### 录制管理

- `acquire()` - 获取录制资源
- `start()` - 开始录制
- `query()` - 查询录制状态
- `stop()` - 停止录制

### EventParser类

#### 事件解析

- `parseEvent()` - 解析Webhook事件
- `isChannelEvent()` - 检查是否为频道事件
- `isUserEvent()` - 检查是否为用户事件

## 最佳实践

### 1. Token管理

- 为不同用户角色生成不同的Token
- 设置合适的Token过期时间
- 在Token即将过期时及时更新

### 2. 录制管理

- 使用合适的录制模式（个人录制vs合流录制）
- 设置合理的最大空闲时间
- 定期查询录制状态
- 妥善处理录制异常

### 3. 错误处理

- 捕获并处理AgoraException
- 记录详细的错误日志
- 实现重试机制

### 4. 安全考虑

- 保护好App Certificate和Customer Secret
- 验证Webhook签名
- 使用HTTPS传输

## 系统要求

- PHP >= 7.4
- cURL扩展
- JSON扩展
- Hash扩展

## 许可证

MIT License

## 更新日志

### v1.0.0 (2024-06-24)

- 初始版本发布
- 支持RTC Token生成（AccessToken2）
- 支持云录制管理
- 支持Webhook事件解析
- 完整的错误处理和异常管理

## 贡献

欢迎提交Pull Request和Issue。

## 支持

如有问题，请：
1. 查看文档和示例代码
2. 搜索已有的Issue
3. 提交新的Issue
4. 联系技术支持
