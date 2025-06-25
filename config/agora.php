<?php

/**
 * Agora RTC SDK 配置示例
 * 
 * 复制此文件为 agora.php 并填入您的实际配置信息
 */

return [
    // 基础配置（必需）
    'app_id' => 'your_app_id_here',
    'app_certificate' => 'your_app_certificate_here',
    
    // RESTful API配置（云录制和Webhook功能需要）
    'customer_id' => 'your_customer_id_here',
    'customer_secret' => 'your_customer_secret_here',
    
    // API基础URL（可选，默认为官方API地址）
    'api_base_url' => 'https://api.agora.io',
    
    // Token默认配置
    'token' => [
        'default_expire_time' => 86400, // 24小时（秒）
        'default_privilege_expire_time' => 0, // 0表示永不过期
    ],
    
    // 云录制默认配置
    'recording' => [
        'default_mode' => 'composite', // individual, composite, web
        'default_recording_uid' => 999999,
        'default_max_idle_time' => 30, // 秒
        
        // 默认录制配置
        'default_config' => [
            'channelType' => 0, // 0=通信，1=直播
            'streamTypes' => 2, // 0=仅音频，1=仅视频，2=音视频
            'subscribeVideoUids' => ['#allstream#'],
            'subscribeAudioUids' => ['#allstream#'],
        ],
        
        // 合流录制默认转码配置
        'default_transcoding_config' => [
            'height' => 640,
            'width' => 360,
            'bitrate' => 500,
            'fps' => 15,
            'mixedVideoLayout' => 1, // 0=悬浮布局，1=自适应布局，2=垂直布局
            'backgroundColor' => '#000000',
        ],
    ],
    
    // 存储配置示例
    'storage' => [
        // Amazon S3
        'amazon_s3' => [
            'vendor' => 0, // CloudRecordingClient::VENDOR_AMAZON_S3
            'region' => 0, // 0=US_EAST_1, 1=US_EAST_2, 2=US_WEST_1, 3=US_WEST_2, 等
            'bucket' => 'your-s3-bucket-name',
            'accessKey' => 'your-s3-access-key',
            'secretKey' => 'your-s3-secret-key',
            'fileNamePrefix' => ['agora-recordings'], // 可选的文件路径前缀
        ],
        
        // 阿里云OSS
        'alibaba_oss' => [
            'vendor' => 1, // CloudRecordingClient::VENDOR_ALIBABA_CLOUD
            'region' => 0, // 0=CN_Hangzhou, 1=CN_Shanghai, 2=CN_Qingdao, 等
            'bucket' => 'your-oss-bucket-name',
            'accessKey' => 'your-oss-access-key',
            'secretKey' => 'your-oss-secret-key',
            'fileNamePrefix' => ['agora-recordings'],
        ],
        
        // 腾讯云COS
        'tencent_cos' => [
            'vendor' => 2, // CloudRecordingClient::VENDOR_TENCENT_CLOUD
            'region' => 0, // 0=AP_Beijing_1, 1=AP_Beijing, 2=AP_Shanghai, 等
            'bucket' => 'your-cos-bucket-name',
            'accessKey' => 'your-cos-access-key',
            'secretKey' => 'your-cos-secret-key',
            'fileNamePrefix' => ['agora-recordings'],
        ],
        
        // 华为云OBS
        'huawei_obs' => [
            'vendor' => 6, // CloudRecordingClient::VENDOR_HUAWEI_CLOUD
            'region' => 0,
            'bucket' => 'your-obs-bucket-name',
            'accessKey' => 'your-obs-access-key',
            'secretKey' => 'your-obs-secret-key',
            'fileNamePrefix' => ['agora-recordings'],
        ],
        
        // 七牛云Kodo
        'qiniu_kodo' => [
            'vendor' => 8, // CloudRecordingClient::VENDOR_QINIU_CLOUD
            'region' => 0,
            'bucket' => 'your-kodo-bucket-name',
            'accessKey' => 'your-kodo-access-key',
            'secretKey' => 'your-kodo-secret-key',
            'fileNamePrefix' => ['agora-recordings'],
        ],
    ],
    
    // Webhook配置
    'webhook' => [
        'verify_signature' => true, // 是否验证签名
        'allowed_events' => [
            101, // channel_create
            102, // channel_destroy
            103, // broadcaster_join_channel
            104, // broadcaster_leave_channel
            105, // audience_join_channel
            106, // audience_leave_channel
            111, // client_role_change_to_broadcaster
            112, // client_role_change_to_audience
        ],
    ],
    
    // HTTP客户端配置
    'http' => [
        'timeout' => 30, // 请求超时时间（秒）
        'user_agent' => 'Agora-RTC-PHP-SDK/1.0.0',
    ],
    
    // 日志配置
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'file' => '/var/log/agora-rtc-sdk.log',
    ],
    
    // 环境配置
    'environment' => 'production', // development, testing, production
    
    // 调试模式
    'debug' => false,
];
