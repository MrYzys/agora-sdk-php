<?php

/**
 * Agora Webhook事件处理示例
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Agora\Sdk\AgoraSdk;
use Agora\Sdk\Webhook\EventParser;
use Agora\Sdk\Exceptions\AgoraException;

// 配置信息
$config = [
    'app_id' => 'your_app_id',
    'app_certificate' => 'your_app_certificate',
    'customer_id' => 'your_customer_id',
    'customer_secret' => 'your_customer_secret',
];

// 设置响应头
header('Content-Type: application/json');

try {
    // 初始化SDK
    $sdk = AgoraRtcSdk::create(
        $config['app_id'],
        $config['app_certificate'],
        $config['customer_id'],
        $config['customer_secret']
    );

    // 获取请求数据
    $requestBody = file_get_contents('php://input');
    $headers = getallheaders();

    // 记录原始请求（用于调试）
    error_log("Webhook请求体: " . $requestBody);
    error_log("Webhook请求头: " . json_encode($headers));

    // 解析事件
    $event = $sdk->parseWebhookEvent($requestBody, $headers, true);

    // 记录解析后的事件
    error_log("解析后的事件: " . json_encode($event, JSON_PRETTY_PRINT));

    // 根据事件类型处理
    $response = handleEvent($event);

    // 返回成功响应
    echo json_encode([
        'status' => 'success',
        'message' => 'Event processed successfully',
        'event_type' => $event['event_name'],
        'notice_id' => $event['notice_id'],
        'response' => $response,
    ]);

} catch (AgoraException $e) {
    // Agora相关错误
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'error_type' => 'agora_error',
        'error_code' => $e->getErrorCode(),
        'message' => $e->getMessage(),
        'details' => $e->getErrorDetails(),
    ]);
    
    error_log("Agora错误: " . $e->getMessage());

} catch (Exception $e) {
    // 系统错误
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error_type' => 'system_error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    
    error_log("系统错误: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

/**
 * 处理不同类型的事件
 * 
 * @param array $event 解析后的事件数据
 * @return array 处理结果
 */
function handleEvent(array $event): array
{
    $eventType = $event['event_type'];
    $payload = $event['payload'];
    $response = [];

    switch ($eventType) {
        case EventParser::EVENT_CHANNEL_CREATE:
            $response = handleChannelCreate($payload);
            break;

        case EventParser::EVENT_CHANNEL_DESTROY:
            $response = handleChannelDestroy($payload);
            break;

        case EventParser::EVENT_BROADCASTER_JOIN:
            $response = handleBroadcasterJoin($payload);
            break;

        case EventParser::EVENT_BROADCASTER_LEAVE:
            $response = handleBroadcasterLeave($payload);
            break;

        case EventParser::EVENT_AUDIENCE_JOIN:
            $response = handleAudienceJoin($payload);
            break;

        case EventParser::EVENT_AUDIENCE_LEAVE:
            $response = handleAudienceLeave($payload);
            break;

        case EventParser::EVENT_CLIENT_ROLE_CHANGE_TO_BROADCASTER:
            $response = handleRoleChangeToBroadcaster($payload);
            break;

        case EventParser::EVENT_CLIENT_ROLE_CHANGE_TO_AUDIENCE:
            $response = handleRoleChangeToAudience($payload);
            break;

        default:
            $response = ['message' => 'Unknown event type', 'event_type' => $eventType];
            break;
    }

    return $response;
}

/**
 * 处理频道创建事件
 */
function handleChannelCreate(array $payload): array
{
    $channelName = $payload['channelName'];
    $timestamp = $payload['ts'];
    
    error_log("频道创建: {$channelName} at " . date('Y-m-d H:i:s', $timestamp));
    
    // 这里可以添加您的业务逻辑，例如：
    // - 在数据库中记录频道创建
    // - 发送通知给管理员
    // - 初始化频道相关资源
    
    return [
        'action' => 'channel_created',
        'channel_name' => $channelName,
        'created_at' => date('Y-m-d H:i:s', $timestamp),
    ];
}

/**
 * 处理频道销毁事件
 */
function handleChannelDestroy(array $payload): array
{
    $channelName = $payload['channelName'];
    $timestamp = $payload['ts'];
    $lastUid = $payload['lastUid'] ?? null;
    
    error_log("频道销毁: {$channelName}, 最后用户: {$lastUid}");
    
    // 业务逻辑：
    // - 清理频道相关资源
    // - 统计频道使用时长
    // - 发送频道结束通知
    
    return [
        'action' => 'channel_destroyed',
        'channel_name' => $channelName,
        'last_uid' => $lastUid,
        'destroyed_at' => date('Y-m-d H:i:s', $timestamp),
    ];
}

/**
 * 处理主播加入事件
 */
function handleBroadcasterJoin(array $payload): array
{
    $channelName = $payload['channelName'];
    $uid = $payload['uid'];
    $platform = $payload['platform_name'] ?? 'Unknown';
    $account = $payload['account'] ?? '';
    
    error_log("主播加入: UID {$uid} 加入频道 {$channelName}, 平台: {$platform}");
    
    // 业务逻辑：
    // - 更新在线用户列表
    // - 发送用户加入通知
    // - 记录用户活动日志
    
    return [
        'action' => 'broadcaster_joined',
        'channel_name' => $channelName,
        'uid' => $uid,
        'platform' => $platform,
        'account' => $account,
    ];
}

/**
 * 处理主播离开事件
 */
function handleBroadcasterLeave(array $payload): array
{
    $channelName = $payload['channelName'];
    $uid = $payload['uid'];
    $reason = $payload['reason'] ?? 0;
    $reasonText = $payload['leave_reason_text'] ?? 'Unknown';
    $duration = $payload['duration'] ?? 0;
    $durationFormatted = $payload['duration_formatted'] ?? '';
    
    error_log("主播离开: UID {$uid} 离开频道 {$channelName}, 原因: {$reasonText}, 持续时间: {$durationFormatted}");
    
    // 业务逻辑：
    // - 更新在线用户列表
    // - 统计用户在线时长
    // - 处理异常离开情况
    
    $response = [
        'action' => 'broadcaster_left',
        'channel_name' => $channelName,
        'uid' => $uid,
        'reason' => $reasonText,
        'duration' => $duration,
        'duration_formatted' => $durationFormatted,
    ];

    // 处理异常用户
    if ($reason === 999) {
        error_log("检测到异常用户: UID {$uid}, 建议踢出");
        $response['warning'] = 'Abnormal user detected, consider kicking';
    }

    return $response;
}

/**
 * 处理观众加入事件
 */
function handleAudienceJoin(array $payload): array
{
    $channelName = $payload['channelName'];
    $uid = $payload['uid'];
    
    error_log("观众加入: UID {$uid} 加入频道 {$channelName}");
    
    return [
        'action' => 'audience_joined',
        'channel_name' => $channelName,
        'uid' => $uid,
    ];
}

/**
 * 处理观众离开事件
 */
function handleAudienceLeave(array $payload): array
{
    $channelName = $payload['channelName'];
    $uid = $payload['uid'];
    $duration = $payload['duration'] ?? 0;
    
    error_log("观众离开: UID {$uid} 离开频道 {$channelName}, 观看时长: {$duration}秒");
    
    return [
        'action' => 'audience_left',
        'channel_name' => $channelName,
        'uid' => $uid,
        'duration' => $duration,
    ];
}

/**
 * 处理角色变更为主播事件
 */
function handleRoleChangeToBroadcaster(array $payload): array
{
    $channelName = $payload['channelName'];
    $uid = $payload['uid'];
    
    error_log("角色变更: UID {$uid} 在频道 {$channelName} 中变为主播");
    
    return [
        'action' => 'role_changed_to_broadcaster',
        'channel_name' => $channelName,
        'uid' => $uid,
    ];
}

/**
 * 处理角色变更为观众事件
 */
function handleRoleChangeToAudience(array $payload): array
{
    $channelName = $payload['channelName'];
    $uid = $payload['uid'];
    
    error_log("角色变更: UID {$uid} 在频道 {$channelName} 中变为观众");
    
    return [
        'action' => 'role_changed_to_audience',
        'channel_name' => $channelName,
        'uid' => $uid,
    ];
}
