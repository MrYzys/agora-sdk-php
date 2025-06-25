<?php

namespace Agora\RtcSdk;

use Agora\RtcSdk\Config\AgoraConfig;
use Agora\RtcSdk\TokenBuilder\RtcTokenBuilder2;
use Agora\RtcSdk\CloudRecording\CloudRecordingClient;
use Agora\RtcSdk\Webhook\EventParser;
use Agora\RtcSdk\Exceptions\AgoraException;

/**
 * Agora RTC SDK主类
 *
 * @package Agora\RtcSdk
 */
class AgoraRtcSdk
{
    /**
     * SDK版本
     */
    const VERSION = '1.0.0';

    /**
     * Agora配置
     *
     * @var AgoraConfig
     */
    private $config;

    /**
     * 云录制客户端
     *
     * @var CloudRecordingClient|null
     */
    private $cloudRecording;

    /**
     * Webhook事件解析器
     *
     * @var EventParser|null
     */
    private $eventParser;

    /**
     * 构造函数
     *
     * @param AgoraConfig $config Agora配置
     */
    public function __construct(AgoraConfig $config)
    {
        if (!$config->isValid()) {
            throw AgoraException::configError('Invalid Agora configuration');
        }

        $this->config = $config;
    }

    /**
     * 创建SDK实例
     *
     * @param string $appId App ID
     * @param string $appCertificate App Certificate
     * @param string $customerId Customer ID (可选，用于RESTful API)
     * @param string $customerSecret Customer Secret (可选，用于RESTful API)
     * @return self
     */
    public static function create(
        string $appId,
        string $appCertificate,
        string $customerId = '',
        string $customerSecret = ''
    ): self {
        $config = new AgoraConfig($appId, $appCertificate, $customerId, $customerSecret);
        return new self($config);
    }

    /**
     * 获取配置
     *
     * @return AgoraConfig
     */
    public function getConfig(): AgoraConfig
    {
        return $this->config;
    }

    /**
     * 生成RTC Token
     *
     * @param string $channelName 频道名称
     * @param int $uid 用户ID (0表示任意用户ID)
     * @param int $role 用户角色 (1=发布者, 2=订阅者)
     * @param int $expireTime Token过期时间（秒，默认24小时）
     * @param int $privilegeExpireTime 权限过期时间（秒，0表示永不过期）
     * @return string
     * @throws AgoraException
     */
    public function generateToken(
        string $channelName,
        int $uid = 0,
        int $role = RtcTokenBuilder2::ROLE_PUBLISHER,
        int $expireTime = 86400,
        int $privilegeExpireTime = 0
    ): string {
        return RtcTokenBuilder2::buildTokenWithUid(
            $this->config->getAppId(),
            $this->config->getAppCertificate(),
            $channelName,
            $uid,
            $role,
            $expireTime,
            $privilegeExpireTime
        );
    }

    /**
     * 生成带详细权限的RTC Token
     *
     * @param string $channelName 频道名称
     * @param int $uid 用户ID
     * @param int $expireTime Token过期时间（秒）
     * @param int $joinChannelPrivilegeExpire 加入频道权限过期时间（秒）
     * @param int $pubAudioPrivilegeExpire 发布音频权限过期时间（秒）
     * @param int $pubVideoPrivilegeExpire 发布视频权限过期时间（秒）
     * @param int $pubDataStreamPrivilegeExpire 发布数据流权限过期时间（秒）
     * @return string
     * @throws AgoraException
     */
    public function generateTokenWithPrivileges(
        string $channelName,
        int $uid,
        int $expireTime = 86400,
        int $joinChannelPrivilegeExpire = 0,
        int $pubAudioPrivilegeExpire = 0,
        int $pubVideoPrivilegeExpire = 0,
        int $pubDataStreamPrivilegeExpire = 0
    ): string {
        return RtcTokenBuilder2::buildTokenWithUidAndPrivilege(
            $this->config->getAppId(),
            $this->config->getAppCertificate(),
            $channelName,
            $uid,
            $expireTime,
            $joinChannelPrivilegeExpire,
            $pubAudioPrivilegeExpire,
            $pubVideoPrivilegeExpire,
            $pubDataStreamPrivilegeExpire
        );
    }

    /**
     * 获取云录制客户端
     *
     * @return CloudRecordingClient
     * @throws AgoraException
     */
    public function getCloudRecording(): CloudRecordingClient
    {
        if ($this->cloudRecording === null) {
            if (!$this->config->isRestfulApiConfigValid()) {
                throw AgoraException::configError(
                    'Cloud recording requires Customer ID and Customer Secret'
                );
            }
            $this->cloudRecording = new CloudRecordingClient($this->config);
        }

        return $this->cloudRecording;
    }

    /**
     * 获取Webhook事件解析器
     *
     * @return EventParser
     * @throws AgoraException
     */
    public function getEventParser(): EventParser
    {
        if ($this->eventParser === null) {
            if (!$this->config->isRestfulApiConfigValid()) {
                throw AgoraException::configError(
                    'Event parser requires Customer Secret for signature verification'
                );
            }
            $this->eventParser = new EventParser($this->config->getCustomerSecret());
        }

        return $this->eventParser;
    }

    /**
     * 创建房间（频道）- 这是一个逻辑概念，实际上频道在第一个用户加入时自动创建
     *
     * @param string $channelName 频道名称
     * @param array $options 选项
     * @return array
     */
    public function createRoom(string $channelName, array $options = []): array
    {
        if (!RtcTokenBuilder2::isValidChannelName($channelName)) {
            throw AgoraException::configError('Invalid channel name format');
        }

        // 生成默认的管理员Token
        $adminUid = $options['admin_uid'] ?? 1;
        $tokenExpireTime = $options['token_expire_time'] ?? 86400; // 24小时

        $adminToken = $this->generateToken(
            $channelName,
            $adminUid,
            RtcTokenBuilder2::ROLE_PUBLISHER,
            $tokenExpireTime
        );

        return [
            'channel_name' => $channelName,
            'admin_uid' => $adminUid,
            'admin_token' => $adminToken,
            'token_expire_time' => $tokenExpireTime,
            'created_at' => time(),
        ];
    }

    /**
     * 为用户生成加入房间的Token
     *
     * @param string $channelName 频道名称
     * @param int $uid 用户ID
     * @param bool $isPublisher 是否为发布者
     * @param int $expireTime Token过期时间（秒）
     * @return array
     */
    public function generateUserToken(
        string $channelName,
        int $uid,
        bool $isPublisher = true,
        int $expireTime = 86400
    ): array {
        $role = $isPublisher ? RtcTokenBuilder2::ROLE_PUBLISHER : RtcTokenBuilder2::ROLE_SUBSCRIBER;

        $token = $this->generateToken($channelName, $uid, $role, $expireTime);

        return [
            'channel_name' => $channelName,
            'uid' => $uid,
            'token' => $token,
            'role' => $role,
            'is_publisher' => $isPublisher,
            'expire_time' => $expireTime,
            'generated_at' => time(),
        ];
    }

    /**
     * 开始录制房间
     *
     * @param string $channelName 频道名称
     * @param array $storageConfig 存储配置
     * @param array $options 录制选项
     * @return array
     * @throws AgoraException
     */
    public function startRecording(
        string $channelName,
        array $storageConfig,
        array $options = []
    ): array {
        $cloudRecording = $this->getCloudRecording();

        // 生成录制服务的UID和Token
        $recordingUid = $options['recording_uid'] ?? 999999;
        $tokenExpireTime = $options['token_expire_time'] ?? 86400;

        $recordingToken = $this->generateToken(
            $channelName,
            $recordingUid,
            RtcTokenBuilder2::ROLE_SUBSCRIBER,
            $tokenExpireTime
        );

        // 获取资源ID
        $acquireResult = $cloudRecording->acquire($channelName, $recordingUid);

        // 开始录制
        $mode = $options['mode'] ?? CloudRecordingClient::MODE_COMPOSITE;
        $recordingConfig = $options['recording_config'] ?? [];

        $startResult = $cloudRecording->start(
            $acquireResult['resource_id'],
            $channelName,
            $recordingUid,
            $recordingToken,
            $storageConfig,
            $mode,
            $recordingConfig
        );

        return array_merge($acquireResult, $startResult, [
            'recording_token' => $recordingToken,
            'started_at' => time(),
        ]);
    }

    /**
     * 停止录制房间
     *
     * @param string $resourceId 资源ID
     * @param string $sid 录制ID
     * @param string $channelName 频道名称
     * @param int $recordingUid 录制服务的用户ID
     * @param string $mode 录制模式
     * @return array
     * @throws AgoraException
     */
    public function stopRecording(
        string $resourceId,
        string $sid,
        string $channelName,
        int $recordingUid,
        string $mode = CloudRecordingClient::MODE_COMPOSITE
    ): array {
        $cloudRecording = $this->getCloudRecording();

        $result = $cloudRecording->stop($resourceId, $sid, $channelName, $recordingUid, $mode);

        return array_merge($result, [
            'stopped_at' => time(),
        ]);
    }

    /**
     * 查询录制状态
     *
     * @param string $resourceId 资源ID
     * @param string $sid 录制ID
     * @param string $mode 录制模式
     * @return array
     * @throws AgoraException
     */
    public function queryRecording(
        string $resourceId,
        string $sid,
        string $mode = CloudRecordingClient::MODE_COMPOSITE
    ): array {
        $cloudRecording = $this->getCloudRecording();

        return $cloudRecording->query($resourceId, $sid, $mode);
    }

    /**
     * 解析Webhook事件
     *
     * @param string $requestBody 请求体
     * @param array $headers 请求头
     * @param bool $verifySignature 是否验证签名
     * @return array
     * @throws AgoraException
     */
    public function parseWebhookEvent(
        string $requestBody,
        array $headers = [],
        bool $verifySignature = true
    ): array {
        $eventParser = $this->getEventParser();

        return $eventParser->parseEvent($requestBody, $headers, $verifySignature);
    }

    /**
     * 获取SDK版本
     *
     * @return string
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }
}
