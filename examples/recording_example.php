<?php

/**
 * Agora 云录制完整示例
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Agora\RtcSdk\AgoraRtcSdk;
use Agora\RtcSdk\CloudRecording\CloudRecordingClient;
use Agora\RtcSdk\Exceptions\AgoraException;

// 配置信息
$config = [
    'app_id' => 'your_app_id',
    'app_certificate' => 'your_app_certificate',
    'customer_id' => 'your_customer_id',
    'customer_secret' => 'your_customer_secret',
];

// 存储配置
$storageConfigs = [
    // Amazon S3
    'amazon_s3' => [
        'vendor' => CloudRecordingClient::VENDOR_AMAZON_S3,
        'region' => 0, // US_EAST_1
        'bucket' => 'your-s3-bucket',
        'accessKey' => 'your-access-key',
        'secretKey' => 'your-secret-key',
    ],
    
    // 阿里云OSS
    'alibaba_oss' => [
        'vendor' => CloudRecordingClient::VENDOR_ALIBABA_CLOUD,
        'region' => 0, // CN_Hangzhou
        'bucket' => 'your-oss-bucket',
        'accessKey' => 'your-access-key',
        'secretKey' => 'your-secret-key',
    ],
    
    // 腾讯云COS
    'tencent_cos' => [
        'vendor' => CloudRecordingClient::VENDOR_TENCENT_CLOUD,
        'region' => 0, // AP_Beijing_1
        'bucket' => 'your-cos-bucket',
        'accessKey' => 'your-access-key',
        'secretKey' => 'your-secret-key',
    ],
];

try {
    // 初始化SDK
    echo "=== 初始化Agora RTC SDK ===\n";
    $sdk = AgoraRtcSdk::create(
        $config['app_id'],
        $config['app_certificate'],
        $config['customer_id'],
        $config['customer_secret']
    );

    // 频道信息
    $channelName = 'recording_test_' . time();
    $recordingUid = 999999;

    echo "频道名称: {$channelName}\n";
    echo "录制UID: {$recordingUid}\n\n";

    // 选择存储配置（这里使用Amazon S3作为示例）
    $selectedStorage = 'amazon_s3';
    $storageConfig = CloudRecordingClient::createStorageConfig(
        $storageConfigs[$selectedStorage]['vendor'],
        $storageConfigs[$selectedStorage]['region'],
        $storageConfigs[$selectedStorage]['bucket'],
        $storageConfigs[$selectedStorage]['accessKey'],
        $storageConfigs[$selectedStorage]['secretKey'],
        [
            'prefix' => ['directory1', 'directory2'], // 文件路径前缀
        ]
    );

    echo "=== 存储配置 ===\n";
    echo "存储厂商: {$selectedStorage}\n";
    echo "存储桶: {$storageConfigs[$selectedStorage]['bucket']}\n\n";

    // 录制配置选项
    $recordingOptions = [
        'recording_uid' => $recordingUid,
        'token_expire_time' => 3600,
        'mode' => CloudRecordingClient::MODE_COMPOSITE, // 合流录制
        'recording_config' => [
            'channelType' => CloudRecordingClient::CHANNEL_TYPE_COMMUNICATION,
            'streamTypes' => 2, // 录制音视频
            'maxIdleTime' => 30, // 最大空闲时间30秒
            'subscribeVideoUids' => ['#allstream#'], // 订阅所有视频流
            'subscribeAudioUids' => ['#allstream#'], // 订阅所有音频流
            'transcodingConfig' => [
                'height' => 640,
                'width' => 360,
                'bitrate' => 500,
                'fps' => 15,
                'mixedVideoLayout' => 1, // 悬浮布局
                'backgroundColor' => '#000000',
            ],
        ],
    ];

    // 开始录制
    echo "=== 开始录制 ===\n";
    $recording = $sdk->startRecording($channelName, $storageConfig, $recordingOptions);

    echo "录制已开始:\n";
    echo "  资源ID: {$recording['resource_id']}\n";
    echo "  录制ID: {$recording['sid']}\n";
    echo "  录制UID: {$recording['uid']}\n";
    echo "  录制模式: {$recording['mode']}\n";
    echo "  开始时间: " . date('Y-m-d H:i:s', $recording['started_at']) . "\n\n";

    // 模拟录制过程中的状态查询
    echo "=== 录制状态监控 ===\n";
    $maxQueries = 5;
    $queryInterval = 10; // 10秒查询一次

    for ($i = 1; $i <= $maxQueries; $i++) {
        echo "第 {$i} 次状态查询:\n";
        
        try {
            $status = $sdk->queryRecording(
                $recording['resource_id'],
                $recording['sid'],
                $recording['mode']
            );

            echo "  录制状态: {$status['status']}\n";
            echo "  上传状态: {$status['upload_status']}\n";
            
            if (!empty($status['file_list'])) {
                echo "  文件列表:\n";
                foreach ($status['file_list'] as $file) {
                    $fileName = $file['filename'] ?? 'unknown';
                    $fileSize = isset($file['file_size']) ? formatBytes($file['file_size']) : 'unknown';
                    echo "    - {$fileName} ({$fileSize})\n";
                }
            }
            
            echo "\n";

            // 如果不是最后一次查询，等待一段时间
            if ($i < $maxQueries) {
                echo "等待 {$queryInterval} 秒...\n\n";
                sleep($queryInterval);
            }

        } catch (AgoraException $e) {
            echo "  查询失败: {$e->getMessage()}\n\n";
            
            // 如果是404错误，可能录制还没开始或已经结束
            if ($e->getCode() === 404) {
                echo "  录制可能还没开始或已经结束\n\n";
            }
        }
    }

    // 停止录制
    echo "=== 停止录制 ===\n";
    $result = $sdk->stopRecording(
        $recording['resource_id'],
        $recording['sid'],
        $channelName,
        $recordingUid,
        $recording['mode']
    );

    echo "录制已停止:\n";
    echo "  最终上传状态: {$result['upload_status']}\n";
    echo "  停止时间: " . date('Y-m-d H:i:s', $result['stopped_at']) . "\n";

    if (!empty($result['file_list'])) {
        echo "  最终文件列表:\n";
        foreach ($result['file_list'] as $file) {
            $fileName = $file['filename'] ?? 'unknown';
            $fileSize = isset($file['file_size']) ? formatBytes($file['file_size']) : 'unknown';
            echo "    - {$fileName} ({$fileSize})\n";
        }
    }

    echo "\n=== 录制完成 ===\n";

} catch (AgoraException $e) {
    echo "Agora错误:\n";
    echo "  错误类型: {$e->getErrorCode()}\n";
    echo "  错误消息: {$e->getMessage()}\n";
    echo "  HTTP状态码: {$e->getCode()}\n";
    
    $details = $e->getErrorDetails();
    if (!empty($details)) {
        echo "  错误详情: " . json_encode($details, JSON_PRETTY_PRINT) . "\n";
    }

} catch (Exception $e) {
    echo "系统错误: {$e->getMessage()}\n";
    echo "文件: {$e->getFile()}:{$e->getLine()}\n";
}

/**
 * 格式化字节大小
 * 
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * 录制配置示例
 */
function getRecordingConfigExamples(): array
{
    return [
        // 个人录制配置
        'individual' => [
            'mode' => CloudRecordingClient::MODE_INDIVIDUAL,
            'recording_config' => [
                'channelType' => CloudRecordingClient::CHANNEL_TYPE_COMMUNICATION,
                'streamTypes' => 2,
                'maxIdleTime' => 30,
                'subscribeVideoUids' => ['123', '456'],
                'subscribeAudioUids' => ['123', '456'],
            ],
        ],

        // 合流录制配置
        'composite' => [
            'mode' => CloudRecordingClient::MODE_COMPOSITE,
            'recording_config' => [
                'channelType' => CloudRecordingClient::CHANNEL_TYPE_COMMUNICATION,
                'streamTypes' => 2,
                'maxIdleTime' => 30,
                'subscribeVideoUids' => ['#allstream#'],
                'subscribeAudioUids' => ['#allstream#'],
                'transcodingConfig' => [
                    'height' => 640,
                    'width' => 360,
                    'bitrate' => 500,
                    'fps' => 15,
                    'mixedVideoLayout' => 1,
                    'backgroundColor' => '#000000',
                ],
            ],
        ],

        // 网页录制配置
        'web' => [
            'mode' => CloudRecordingClient::MODE_WEB,
            'recording_config' => [
                'channelType' => CloudRecordingClient::CHANNEL_TYPE_COMMUNICATION,
                'streamTypes' => 2,
                'maxIdleTime' => 30,
                'extensionServiceConfig' => [
                    'extensionServices' => [
                        [
                            'serviceName' => 'web_recorder_service',
                            'errorHandlePolicy' => 'error_abort',
                            'serviceParam' => [
                                'url' => 'https://example.com/recording-page',
                                'videoBitrate' => 500,
                                'videoFps' => 15,
                                'mobile' => false,
                                'videoWidth' => 1280,
                                'videoHeight' => 720,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
}
