<?php

namespace Agora\Sdk\Webhook;

use Agora\Sdk\Exceptions\AgoraException;

/**
 * Webhook事件解析器
 *
 * @package Agora\Sdk\Webhook
 */
class EventParser
{
    /**
     * 事件类型：创建频道
     */
    const EVENT_CHANNEL_CREATE = 101;

    /**
     * 事件类型：销毁频道
     */
    const EVENT_CHANNEL_DESTROY = 102;

    /**
     * 事件类型：主播加入频道
     */
    const EVENT_BROADCASTER_JOIN = 103;

    /**
     * 事件类型：主播离开频道
     */
    const EVENT_BROADCASTER_LEAVE = 104;

    /**
     * 事件类型：观众加入频道
     */
    const EVENT_AUDIENCE_JOIN = 105;

    /**
     * 事件类型：观众离开频道
     */
    const EVENT_AUDIENCE_LEAVE = 106;

    /**
     * 事件类型：观众变为主播
     */
    const EVENT_CLIENT_ROLE_CHANGE_TO_BROADCASTER = 111;

    /**
     * 事件类型：主播变为观众
     */
    const EVENT_CLIENT_ROLE_CHANGE_TO_AUDIENCE = 112;

    /**
     * 离开原因映射
     */
    const LEAVE_REASONS = [
        1 => 'Normal leave',
        2 => 'Connection timeout',
        3 => 'Permission issue',
        4 => 'Server internal reason',
        5 => 'Device switch',
        9 => 'Multiple IP addresses',
        10 => 'Network connection problem',
        999 => 'Abnormal user',
        0 => 'Other reasons',
    ];

    /**
     * 平台映射
     */
    const PLATFORMS = [
        1 => 'Android',
        2 => 'iOS',
        5 => 'Windows',
        6 => 'Linux',
        7 => 'Web',
        8 => 'macOS',
        0 => 'Other platforms',
    ];

    /**
     * Customer Secret (用于验证签名)
     *
     * @var string
     */
    private $customerSecret;

    /**
     * 构造函数
     *
     * @param string $customerSecret Customer Secret
     */
    public function __construct(string $customerSecret)
    {
        $this->customerSecret = $customerSecret;
    }

    /**
     * 解析Webhook事件
     *
     * @param string $requestBody 请求体JSON字符串
     * @param array $headers 请求头
     * @param bool $verifySignature 是否验证签名
     * @return array 解析后的事件数据
     * @throws AgoraException
     */
    public function parseEvent(string $requestBody, array $headers = [], bool $verifySignature = true): array
    {
        // 解析JSON
        $eventData = json_decode($requestBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw AgoraException::webhookError('Invalid JSON format: ' . json_last_error_msg());
        }

        // 验证必要字段
        $this->validateEventData($eventData);

        // 验证签名
        if ($verifySignature) {
            $this->verifySignature($requestBody, $headers);
        }

        // 解析事件
        return $this->parseEventData($eventData);
    }

    /**
     * 验证事件数据结构
     *
     * @param array $eventData
     * @throws AgoraException
     */
    private function validateEventData(array $eventData): void
    {
        $requiredFields = ['noticeId', 'productId', 'eventType', 'notifyMs', 'payload'];

        foreach ($requiredFields as $field) {
            if (!isset($eventData[$field])) {
                throw AgoraException::webhookError("Missing required field: {$field}");
            }
        }

        if (!is_int($eventData['eventType'])) {
            throw AgoraException::webhookError('Event type must be an integer');
        }
    }

    /**
     * 验证Webhook签名
     *
     * @param string $requestBody
     * @param array $headers
     * @throws AgoraException
     */
    private function verifySignature(string $requestBody, array $headers): void
    {
        $signature = null;
        $signatureV2 = null;

        // 查找签名头（不区分大小写）
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            if ($lowerKey === 'agora-signature') {
                $signature = $value;
            } elseif ($lowerKey === 'agora-signature-v2') {
                $signatureV2 = $value;
            }
        }

        // 优先使用V2签名（SHA256）
        if ($signatureV2) {
            $expectedSignature = hash_hmac('sha256', $requestBody, $this->customerSecret);
            if (!hash_equals($expectedSignature, $signatureV2)) {
                throw AgoraException::webhookError('Invalid signature (SHA256)');
            }
        } elseif ($signature) {
            $expectedSignature = hash_hmac('sha1', $requestBody, $this->customerSecret);
            if (!hash_equals($expectedSignature, $signature)) {
                throw AgoraException::webhookError('Invalid signature (SHA1)');
            }
        } else {
            throw AgoraException::webhookError('No signature found in headers');
        }
    }

    /**
     * 解析事件数据
     *
     * @param array $eventData
     * @return array
     */
    private function parseEventData(array $eventData): array
    {
        $parsedEvent = [
            'notice_id' => $eventData['noticeId'],
            'product_id' => $eventData['productId'],
            'event_type' => $eventData['eventType'],
            'event_name' => $this->getEventName($eventData['eventType']),
            'notify_timestamp' => $eventData['notifyMs'],
            'session_id' => $eventData['sid'] ?? null,
            'payload' => $this->parsePayload($eventData['eventType'], $eventData['payload']),
        ];

        return $parsedEvent;
    }

    /**
     * 获取事件名称
     *
     * @param int $eventType
     * @return string
     */
    private function getEventName(int $eventType): string
    {
        $eventNames = [
            self::EVENT_CHANNEL_CREATE => 'channel_create',
            self::EVENT_CHANNEL_DESTROY => 'channel_destroy',
            self::EVENT_BROADCASTER_JOIN => 'broadcaster_join_channel',
            self::EVENT_BROADCASTER_LEAVE => 'broadcaster_leave_channel',
            self::EVENT_AUDIENCE_JOIN => 'audience_join_channel',
            self::EVENT_AUDIENCE_LEAVE => 'audience_leave_channel',
            self::EVENT_CLIENT_ROLE_CHANGE_TO_BROADCASTER => 'client_role_change_to_broadcaster',
            self::EVENT_CLIENT_ROLE_CHANGE_TO_AUDIENCE => 'client_role_change_to_audience',
        ];

        return $eventNames[$eventType] ?? 'unknown_event';
    }

    /**
     * 解析载荷数据
     *
     * @param int $eventType
     * @param array $payload
     * @return array
     */
    private function parsePayload(int $eventType, array $payload): array
    {
        $parsedPayload = $payload;

        // 添加通用字段解析
        if (isset($payload['platform'])) {
            $parsedPayload['platform_name'] = self::PLATFORMS[$payload['platform']] ?? 'Unknown';
        }

        if (isset($payload['reason'])) {
            $parsedPayload['leave_reason_text'] = self::LEAVE_REASONS[$payload['reason']] ?? 'Unknown reason';
        }

        // 转换时间戳
        if (isset($payload['ts'])) {
            $parsedPayload['event_time'] = date('Y-m-d H:i:s', $payload['ts']);
        }

        // 根据事件类型进行特殊处理
        switch ($eventType) {
            case self::EVENT_CHANNEL_CREATE:
            case self::EVENT_CHANNEL_DESTROY:
                // 频道事件无需特殊处理
                break;

            case self::EVENT_BROADCASTER_JOIN:
            case self::EVENT_BROADCASTER_LEAVE:
            case self::EVENT_AUDIENCE_JOIN:
            case self::EVENT_AUDIENCE_LEAVE:
                // 用户事件，添加持续时间格式化
                if (isset($payload['duration'])) {
                    $parsedPayload['duration_formatted'] = $this->formatDuration($payload['duration']);
                }
                break;

            case self::EVENT_CLIENT_ROLE_CHANGE_TO_BROADCASTER:
            case self::EVENT_CLIENT_ROLE_CHANGE_TO_AUDIENCE:
                // 角色变更事件无需特殊处理
                break;
        }

        return $parsedPayload;
    }

    /**
     * 格式化持续时间
     *
     * @param int $seconds
     * @return string
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        } else {
            return sprintf('%02d:%02d', $minutes, $remainingSeconds);
        }
    }

    /**
     * 检查是否为频道事件
     *
     * @param int $eventType
     * @return bool
     */
    public static function isChannelEvent(int $eventType): bool
    {
        return in_array($eventType, [
            self::EVENT_CHANNEL_CREATE,
            self::EVENT_CHANNEL_DESTROY,
        ]);
    }

    /**
     * 检查是否为用户事件
     *
     * @param int $eventType
     * @return bool
     */
    public static function isUserEvent(int $eventType): bool
    {
        return in_array($eventType, [
            self::EVENT_BROADCASTER_JOIN,
            self::EVENT_BROADCASTER_LEAVE,
            self::EVENT_AUDIENCE_JOIN,
            self::EVENT_AUDIENCE_LEAVE,
            self::EVENT_CLIENT_ROLE_CHANGE_TO_BROADCASTER,
            self::EVENT_CLIENT_ROLE_CHANGE_TO_AUDIENCE,
        ]);
    }
}
