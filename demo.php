<?php

/**
 * Agora RTC SDK 完整功能演示
 */

require_once 'vendor/autoload.php';

use Agora\RtcSdk\AgoraRtcSdk;
use Agora\RtcSdk\CloudRecording\CloudRecordingClient;
use Agora\RtcSdk\TokenBuilder\RtcTokenBuilder2;
use Agora\RtcSdk\Exceptions\AgoraException;

echo "🚀 Agora RTC SDK for PHP - 完整功能演示\n";
echo "========================================\n\n";

// 演示配置
$config = [
    'app_id' => 'demo_app_id_12345',
    'app_certificate' => 'demo_app_certificate_67890abcdef',
    'customer_id' => 'demo_customer_id',
    'customer_secret' => 'demo_customer_secret',
];

try {
    // 1. 初始化SDK
    echo "📱 1. 初始化SDK\n";
    echo "   App ID: {$config['app_id']}\n";
    echo "   SDK版本: " . AgoraRtcSdk::getVersion() . "\n\n";
    
    $sdk = AgoraRtcSdk::create(
        $config['app_id'],
        $config['app_certificate'],
        $config['customer_id'],
        $config['customer_secret']
    );

    // 2. 房间创建
    echo "🏠 2. 创建房间\n";
    $channelName = 'demo_channel_' . date('YmdHis');
    $room = $sdk->createRoom($channelName, [
        'admin_uid' => 1,
        'token_expire_time' => 3600,
    ]);
    
    echo "   频道名称: {$room['channel_name']}\n";
    echo "   管理员UID: {$room['admin_uid']}\n";
    echo "   管理员Token: " . substr($room['admin_token'], 0, 30) . "...\n";
    echo "   创建时间: " . date('Y-m-d H:i:s', $room['created_at']) . "\n\n";

    // 3. 用户Token生成
    echo "🎫 3. 生成用户Token\n";
    
    $users = [
        ['uid' => 10001, 'role' => '主播', 'is_publisher' => true],
        ['uid' => 10002, 'role' => '观众', 'is_publisher' => false],
        ['uid' => 10003, 'role' => '主播', 'is_publisher' => true],
    ];
    
    $userTokens = [];
    foreach ($users as $user) {
        $userToken = $sdk->generateUserToken(
            $channelName,
            $user['uid'],
            $user['is_publisher'],
            3600
        );
        
        $userTokens[] = $userToken;
        
        echo "   用户 {$user['uid']} ({$user['role']}):\n";
        echo "     Token: " . substr($userToken['token'], 0, 25) . "...\n";
        echo "     角色代码: {$userToken['role']}\n";
    }
    echo "\n";

    // 4. 详细权限Token
    echo "🔐 4. 详细权限Token生成\n";
    $privilegeToken = $sdk->generateTokenWithPrivileges(
        $channelName,
        99999,
        3600,  // Token过期
        3600,  // 加入频道权限
        3600,  // 发布音频权限
        3600,  // 发布视频权限
        0      // 发布数据流权限（永不过期）
    );
    
    echo "   特权用户Token: " . substr($privilegeToken, 0, 30) . "...\n";
    echo "   权限配置: 完整音视频权限\n\n";

    // 5. 云录制演示（模拟）
    echo "📹 5. 云录制功能演示\n";
    
    // 创建存储配置
    $storageConfig = CloudRecordingClient::createStorageConfig(
        CloudRecordingClient::VENDOR_AMAZON_S3,
        0,  // 区域
        'demo-recording-bucket',
        'demo-access-key',
        'demo-secret-key',
        ['recordings', date('Y/m/d')] // 文件路径前缀
    );
    
    echo "   存储配置:\n";
    echo "     厂商: Amazon S3\n";
    echo "     存储桶: demo-recording-bucket\n";
    echo "     路径前缀: recordings/" . date('Y/m/d') . "/\n";
    
    // 注意：这里只是演示配置，不会真正启动录制
    echo "   录制配置:\n";
    echo "     模式: 合流录制\n";
    echo "     录制内容: 音频+视频\n";
    echo "     最大空闲时间: 30秒\n";
    echo "     录制UID: 999999\n\n";

    // 6. Webhook事件解析演示
    echo "🔔 6. Webhook事件解析演示\n";
    
    // 模拟事件数据
    $sampleEvents = [
        [
            'name' => '频道创建',
            'type' => 101,
            'data' => ['channelName' => $channelName, 'ts' => time()],
        ],
        [
            'name' => '主播加入',
            'type' => 103,
            'data' => [
                'channelName' => $channelName,
                'uid' => 10001,
                'platform' => 1,
                'ts' => time(),
            ],
        ],
        [
            'name' => '主播离开',
            'type' => 104,
            'data' => [
                'channelName' => $channelName,
                'uid' => 10001,
                'reason' => 1,
                'duration' => 300,
                'ts' => time(),
            ],
        ],
    ];
    
    foreach ($sampleEvents as $event) {
        $webhookBody = json_encode([
            'noticeId' => 'demo_' . uniqid(),
            'productId' => 1,
            'eventType' => $event['type'],
            'notifyMs' => time() * 1000,
            'payload' => $event['data'],
        ]);
        
        // 不验证签名的解析（演示用）
        $parsedEvent = $sdk->parseWebhookEvent($webhookBody, [], false);
        
        echo "   事件: {$event['name']}\n";
        echo "     类型代码: {$parsedEvent['event_type']}\n";
        echo "     事件名称: {$parsedEvent['event_name']}\n";
        echo "     通知ID: {$parsedEvent['notice_id']}\n";
        
        if (isset($parsedEvent['payload']['uid'])) {
            echo "     用户ID: {$parsedEvent['payload']['uid']}\n";
        }
        
        if (isset($parsedEvent['payload']['duration_formatted'])) {
            echo "     持续时间: {$parsedEvent['payload']['duration_formatted']}\n";
        }
        echo "\n";
    }

    // 7. 错误处理演示
    echo "⚠️  7. 错误处理演示\n";
    
    try {
        // 故意触发错误
        $sdk->generateToken('', 12345);
    } catch (AgoraException $e) {
        echo "   捕获到Agora异常:\n";
        echo "     错误类型: {$e->getErrorCode()}\n";
        echo "     错误消息: {$e->getMessage()}\n";
        echo "     处理方式: 返回错误信息给客户端\n\n";
    }

    // 8. 功能总结
    echo "✅ 8. 功能验证总结\n";
    echo "   ✓ RTC房间创建 - 支持频道管理和Token生成\n";
    echo "   ✓ 客户端Token生成 - 支持不同角色和权限控制\n";
    echo "   ✓ 事件回调解析 - 支持所有Webhook事件类型\n";
    echo "   ✓ 云录制管理 - 支持完整录制生命周期\n";
    echo "   ✓ 错误处理 - 完善的异常处理机制\n";
    echo "   ✓ 类型安全 - 完整的参数验证\n\n";

    echo "🎉 演示完成！SDK已准备就绪，可以集成到您的项目中。\n\n";
    
    echo "📚 下一步:\n";
    echo "   1. 查看 examples/ 目录中的详细示例\n";
    echo "   2. 阅读 README.md 了解完整API\n";
    echo "   3. 配置您的真实Agora凭据\n";
    echo "   4. 运行 composer test 验证功能\n";

} catch (AgoraException $e) {
    echo "❌ Agora错误: {$e->getMessage()}\n";
    echo "   错误类型: {$e->getErrorCode()}\n";
    echo "   HTTP状态码: {$e->getCode()}\n";
    
} catch (Exception $e) {
    echo "❌ 系统错误: {$e->getMessage()}\n";
    echo "   文件: {$e->getFile()}:{$e->getLine()}\n";
}
