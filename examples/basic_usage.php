<?php

/**
 * Agora RTC SDK 基础使用示例
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Agora\Sdk\AgoraSdk;
use Agora\Sdk\CloudRecording\CloudRecordingClient;
use Agora\Sdk\TokenBuilder\RtcTokenBuilder2;
use Agora\Sdk\Exceptions\AgoraException;

// 配置信息（请替换为您的实际配置）
$config = [
    'app_id' => 'test_app_id_12345',
    'app_certificate' => 'test_app_certificate_67890abcdef',
    'customer_id' => 'test_customer_id',
    'customer_secret' => 'test_customer_secret',
];

try {
    // 1. 初始化SDK
    echo "=== 初始化Agora RTC SDK ===\n";
    $sdk = AgoraSdk::create(
        $config['app_id'],
        $config['app_certificate'],
        $config['customer_id'],
        $config['customer_secret']
    );
    echo "SDK版本：" . AgoraSdk::getVersion() . "\n\n";

    // 2. 创建房间
    echo "=== 创建房间 ===\n";
    $channelName = 'test_channel_' . time();
    $room = $sdk->createRoom($channelName, [
        'admin_uid' => 1,
        'token_expire_time' => 3600,
    ]);
    
    echo "频道名称：{$room['channel_name']}\n";
    echo "管理员UID：{$room['admin_uid']}\n";
    echo "管理员Token：{$room['admin_token']}\n";
    echo "Token过期时间：{$room['token_expire_time']}秒\n\n";

    // 3. 为用户生成Token
    echo "=== 生成用户Token ===\n";
    
    // 发布者Token
    $publisherToken = $sdk->generateUserToken($channelName, 12345, true, 3600);
    echo "发布者Token：\n";
    echo "  UID: {$publisherToken['uid']}\n";
    echo "  角色: " . ($publisherToken['is_publisher'] ? '发布者' : '订阅者') . "\n";
    echo "  Token: {$publisherToken['token']}\n\n";

    // 订阅者Token
    $subscriberToken = $sdk->generateUserToken($channelName, 12346, false, 3600);
    echo "订阅者Token：\n";
    echo "  UID: {$subscriberToken['uid']}\n";
    echo "  角色: " . ($subscriberToken['is_publisher'] ? '发布者' : '订阅者') . "\n";
    echo "  Token: {$subscriberToken['token']}\n\n";

    // 4. 详细权限Token生成
    echo "=== 生成详细权限Token ===\n";
    $detailedToken = $sdk->generateTokenWithPrivileges(
        $channelName,
        12347,
        3600,  // Token过期时间
        3600,  // 加入频道权限过期时间
        3600,  // 发布音频权限过期时间
        3600,  // 发布视频权限过期时间
        0      // 发布数据流权限过期时间（0表示永不过期）
    );
    echo "详细权限Token：{$detailedToken}\n\n";

    // 5. 云录制示例（需要配置存储）
    echo "=== 云录制示例 ===\n";
    
    // 创建存储配置（示例使用Amazon S3）
    $storageConfig = CloudRecordingClient::createStorageConfig(
        CloudRecordingClient::VENDOR_AMAZON_S3,
        0,  // 区域
        'your-bucket-name',
        'your-access-key',
        'your-secret-key'
    );

    echo "存储配置已创建\n";

    // 注意：以下录制操作需要有效的存储配置才能成功
    /*
    // 开始录制
    $recording = $sdk->startRecording($channelName, $storageConfig, [
        'recording_uid' => 999999,
        'mode' => CloudRecordingClient::MODE_COMPOSITE,
        'recording_config' => [
            'channelType' => CloudRecordingClient::CHANNEL_TYPE_COMMUNICATION,
            'streamTypes' => 2, // 录制音视频
            'maxIdleTime' => 30,
        ],
    ]);

    echo "录制已开始：\n";
    echo "  资源ID: {$recording['resource_id']}\n";
    echo "  录制ID: {$recording['sid']}\n";
    echo "  录制UID: {$recording['uid']}\n\n";

    // 查询录制状态
    sleep(5); // 等待5秒
    $status = $sdk->queryRecording($recording['resource_id'], $recording['sid']);
    echo "录制状态：{$status['status']}\n";
    echo "上传状态：{$status['upload_status']}\n\n";

    // 停止录制
    $result = $sdk->stopRecording(
        $recording['resource_id'],
        $recording['sid'],
        $channelName,
        999999
    );
    echo "录制已停止\n";
    echo "最终上传状态：{$result['upload_status']}\n";
    */

    echo "=== 示例完成 ===\n";

} catch (AgoraException $e) {
    echo "Agora错误：\n";
    echo "  错误类型：{$e->getErrorCode()}\n";
    echo "  错误消息：{$e->getMessage()}\n";
    echo "  HTTP状态码：{$e->getCode()}\n";
    
    $details = $e->getErrorDetails();
    if (!empty($details)) {
        echo "  错误详情：" . json_encode($details, JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "系统错误：{$e->getMessage()}\n";
    echo "文件：{$e->getFile()}:{$e->getLine()}\n";
}
