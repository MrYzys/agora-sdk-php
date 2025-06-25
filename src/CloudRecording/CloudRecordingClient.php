<?php

namespace Agora\Sdk\CloudRecording;

use Agora\Sdk\Config\AgoraConfig;
use Agora\Sdk\Http\HttpClient;
use Agora\Sdk\Exceptions\AgoraException;

/**
 * 云录制客户端
 *
 * @package Agora\Sdk\CloudRecording
 */
class CloudRecordingClient
{
    /**
     * 录制模式：个人录制
     */
    const MODE_INDIVIDUAL = 'individual';

    /**
     * 录制模式：合流录制
     */
    const MODE_COMPOSITE = 'composite';

    /**
     * 录制模式：网页录制
     */
    const MODE_WEB = 'web';

    /**
     * 频道类型：通信
     */
    const CHANNEL_TYPE_COMMUNICATION = 0;

    /**
     * 频道类型：直播
     */
    const CHANNEL_TYPE_LIVE_BROADCAST = 1;

    /**
     * 云存储厂商：Amazon S3
     */
    const VENDOR_AMAZON_S3 = 0;

    /**
     * 云存储厂商：阿里云OSS
     */
    const VENDOR_ALIBABA_CLOUD = 1;

    /**
     * 云存储厂商：腾讯云COS
     */
    const VENDOR_TENCENT_CLOUD = 2;

    /**
     * 云存储厂商：金山云KS3
     */
    const VENDOR_KINGSOFT_CLOUD = 3;

    /**
     * 云存储厂商：Microsoft Azure
     */
    const VENDOR_MICROSOFT_AZURE = 4;

    /**
     * 云存储厂商：Google Cloud
     */
    const VENDOR_GOOGLE_CLOUD = 5;

    /**
     * 云存储厂商：华为云OBS
     */
    const VENDOR_HUAWEI_CLOUD = 6;

    /**
     * 云存储厂商：百度云BOS
     */
    const VENDOR_BAIDU_CLOUD = 7;

    /**
     * 云存储厂商：七牛云Kodo
     */
    const VENDOR_QINIU_CLOUD = 8;

    /**
     * HTTP客户端
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Agora配置
     *
     * @var AgoraConfig
     */
    private $config;

    /**
     * 构造函数
     *
     * @param AgoraConfig $config
     */
    public function __construct(AgoraConfig $config)
    {
        if (!$config->isRestfulApiConfigValid()) {
            throw AgoraException::configError('RESTful API configuration is incomplete');
        }

        $this->config = $config;
        $this->httpClient = new HttpClient($config);
    }

    /**
     * 获取云录制资源ID
     *
     * @param string $channelName 频道名称
     * @param int $uid 录制服务的用户ID
     * @return array
     * @throws AgoraException
     */
    public function acquire(string $channelName, int $uid): array
    {
        if (empty($channelName)) {
            throw AgoraException::recordingError('Channel name cannot be empty');
        }

        if ($uid <= 0) {
            throw AgoraException::recordingError('UID must be greater than 0');
        }

        $endpoint = sprintf('v1/apps/%s/cloud_recording/acquire', $this->config->getAppId());

        $data = [
            'cname' => $channelName,
            'uid' => (string)$uid,
            'clientRequest' => new \stdClass(), // 空对象
        ];

        try {
            $response = $this->httpClient->post($endpoint, $data);
            return [
                'resource_id' => $response['resourceId'],
                'channel_name' => $channelName,
                'uid' => $uid,
            ];
        } catch (AgoraException $e) {
            throw AgoraException::recordingError(
                'Failed to acquire recording resource: ' . $e->getMessage(),
                $e->getCode(),
                $e->getErrorCode()
            );
        }
    }

    /**
     * 开始云录制
     *
     * @param string $resourceId 资源ID
     * @param string $channelName 频道名称
     * @param int $uid 录制服务的用户ID
     * @param string $token 临时Token
     * @param array $storageConfig 存储配置
     * @param string $mode 录制模式
     * @param array $recordingConfig 录制配置
     * @return array
     * @throws AgoraException
     */
    public function start(
        string $resourceId,
        string $channelName,
        int $uid,
        string $token,
        array $storageConfig,
        string $mode = self::MODE_COMPOSITE,
        array $recordingConfig = []
    ): array {
        $this->validateStartParameters($resourceId, $channelName, $uid, $token, $storageConfig, $mode);

        $endpoint = sprintf(
            'v1/apps/%s/cloud_recording/resourceid/%s/mode/%s/start',
            $this->config->getAppId(),
            $resourceId,
            $mode
        );

        // 默认录制配置
        $defaultRecordingConfig = [
            'channelType' => self::CHANNEL_TYPE_COMMUNICATION,
            'streamTypes' => 2, // 录制音视频
            'maxIdleTime' => 30, // 最大空闲时间30秒
        ];

        $recordingConfig = array_merge($defaultRecordingConfig, $recordingConfig);

        $data = [
            'cname' => $channelName,
            'uid' => (string)$uid,
            'clientRequest' => [
                'token' => $token,
                'storageConfig' => $storageConfig,
                'recordingConfig' => $recordingConfig,
            ],
        ];

        try {
            $response = $this->httpClient->post($endpoint, $data);
            return [
                'resource_id' => $resourceId,
                'sid' => $response['sid'],
                'channel_name' => $channelName,
                'uid' => $uid,
                'mode' => $mode,
            ];
        } catch (AgoraException $e) {
            throw AgoraException::recordingError(
                'Failed to start recording: ' . $e->getMessage(),
                $e->getCode(),
                $e->getErrorCode()
            );
        }
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
    public function query(string $resourceId, string $sid, string $mode = self::MODE_COMPOSITE): array
    {
        if (empty($resourceId)) {
            throw AgoraException::recordingError('Resource ID cannot be empty');
        }

        if (empty($sid)) {
            throw AgoraException::recordingError('SID cannot be empty');
        }

        $endpoint = sprintf(
            'v1/apps/%s/cloud_recording/resourceid/%s/sid/%s/mode/%s/query',
            $this->config->getAppId(),
            $resourceId,
            $sid,
            $mode
        );

        try {
            $response = $this->httpClient->get($endpoint);
            return $this->parseQueryResponse($response);
        } catch (AgoraException $e) {
            throw AgoraException::recordingError(
                'Failed to query recording status: ' . $e->getMessage(),
                $e->getCode(),
                $e->getErrorCode()
            );
        }
    }

    /**
     * 停止录制
     *
     * @param string $resourceId 资源ID
     * @param string $sid 录制ID
     * @param string $channelName 频道名称
     * @param int $uid 录制服务的用户ID
     * @param string $mode 录制模式
     * @return array
     * @throws AgoraException
     */
    public function stop(
        string $resourceId,
        string $sid,
        string $channelName,
        int $uid,
        string $mode = self::MODE_COMPOSITE
    ): array {
        if (empty($resourceId)) {
            throw AgoraException::recordingError('Resource ID cannot be empty');
        }

        if (empty($sid)) {
            throw AgoraException::recordingError('SID cannot be empty');
        }

        $endpoint = sprintf(
            'v1/apps/%s/cloud_recording/resourceid/%s/sid/%s/mode/%s/stop',
            $this->config->getAppId(),
            $resourceId,
            $sid,
            $mode
        );

        $data = [
            'cname' => $channelName,
            'uid' => (string)$uid,
            'clientRequest' => new \stdClass(),
        ];

        try {
            $response = $this->httpClient->post($endpoint, $data);
            return $this->parseStopResponse($response);
        } catch (AgoraException $e) {
            throw AgoraException::recordingError(
                'Failed to stop recording: ' . $e->getMessage(),
                $e->getCode(),
                $e->getErrorCode()
            );
        }
    }

    /**
     * 验证开始录制的参数
     *
     * @param string $resourceId
     * @param string $channelName
     * @param int $uid
     * @param string $token
     * @param array $storageConfig
     * @param string $mode
     * @throws AgoraException
     */
    private function validateStartParameters(
        string $resourceId,
        string $channelName,
        int $uid,
        string $token,
        array $storageConfig,
        string $mode
    ): void {
        if (empty($resourceId)) {
            throw AgoraException::recordingError('Resource ID cannot be empty');
        }

        if (empty($channelName)) {
            throw AgoraException::recordingError('Channel name cannot be empty');
        }

        if ($uid <= 0) {
            throw AgoraException::recordingError('UID must be greater than 0');
        }

        if (empty($token)) {
            throw AgoraException::recordingError('Token cannot be empty');
        }

        if (!in_array($mode, [self::MODE_INDIVIDUAL, self::MODE_COMPOSITE, self::MODE_WEB])) {
            throw AgoraException::recordingError('Invalid recording mode');
        }

        // 验证存储配置
        $requiredStorageFields = ['vendor', 'region', 'bucket', 'accessKey', 'secretKey'];
        foreach ($requiredStorageFields as $field) {
            if (!isset($storageConfig[$field])) {
                throw AgoraException::recordingError("Missing storage config field: {$field}");
            }
        }
    }

    /**
     * 解析查询响应
     *
     * @param array $response
     * @return array
     */
    private function parseQueryResponse(array $response): array
    {
        $serverResponse = $response['serverResponse'] ?? [];

        return [
            'status' => $serverResponse['status'] ?? 'unknown',
            'file_list' => $serverResponse['fileList'] ?? [],
            'upload_status' => $serverResponse['uploadingStatus'] ?? 'unknown',
        ];
    }

    /**
     * 解析停止响应
     *
     * @param array $response
     * @return array
     */
    private function parseStopResponse(array $response): array
    {
        $serverResponse = $response['serverResponse'] ?? [];

        return [
            'upload_status' => $serverResponse['uploadingStatus'] ?? 'unknown',
            'file_list' => $serverResponse['fileList'] ?? [],
            'file_list_mode' => $serverResponse['fileListMode'] ?? 'unknown',
        ];
    }

    /**
     * 创建存储配置
     *
     * @param int $vendor 云存储厂商
     * @param int $region 区域
     * @param string $bucket 存储桶名称
     * @param string $accessKey 访问密钥
     * @param string $secretKey 密钥
     * @param array $fileNamePrefix 文件名前缀配置
     * @return array
     */
    public static function createStorageConfig(
        int $vendor,
        int $region,
        string $bucket,
        string $accessKey,
        string $secretKey,
        array $fileNamePrefix = []
    ): array {
        $config = [
            'vendor' => $vendor,
            'region' => $region,
            'bucket' => $bucket,
            'accessKey' => $accessKey,
            'secretKey' => $secretKey,
        ];

        if (!empty($fileNamePrefix)) {
            $config['fileNamePrefix'] = $fileNamePrefix;
        }

        return $config;
    }
}
