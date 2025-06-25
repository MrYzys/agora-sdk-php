<?php

namespace Agora\Sdk\Exceptions;

use Exception;

/**
 * Agora异常类
 *
 * @package Agora\Sdk\Exceptions
 */
class AgoraException extends Exception
{
    /**
     * 错误代码
     *
     * @var string|null
     */
    private $errorCode;

    /**
     * 错误详情
     *
     * @var array
     */
    private $errorDetails;

    /**
     * 构造函数
     *
     * @param string $message 错误消息
     * @param int $code HTTP状态码
     * @param string|null $errorCode Agora错误代码
     * @param array $errorDetails 错误详情
     * @param Exception|null $previous 上一个异常
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?string $errorCode = null,
        array $errorDetails = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->errorDetails = $errorDetails;
    }

    /**
     * 获取Agora错误代码
     *
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * 获取错误详情
     *
     * @return array
     */
    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }

    /**
     * 创建配置错误异常
     *
     * @param string $message
     * @return self
     */
    public static function configError(string $message): self
    {
        return new self("Configuration Error: {$message}", 0, 'CONFIG_ERROR');
    }

    /**
     * 创建API请求错误异常
     *
     * @param string $message
     * @param int $httpCode
     * @param string|null $errorCode
     * @param array $errorDetails
     * @return self
     */
    public static function apiError(
        string $message,
        int $httpCode,
        ?string $errorCode = null,
        array $errorDetails = []
    ): self {
        return new self("API Error: {$message}", $httpCode, $errorCode, $errorDetails);
    }

    /**
     * 创建Token生成错误异常
     *
     * @param string $message
     * @return self
     */
    public static function tokenError(string $message): self
    {
        return new self("Token Error: {$message}", 0, 'TOKEN_ERROR');
    }

    /**
     * 创建Webhook解析错误异常
     *
     * @param string $message
     * @return self
     */
    public static function webhookError(string $message): self
    {
        return new self("Webhook Error: {$message}", 0, 'WEBHOOK_ERROR');
    }

    /**
     * 创建录制错误异常
     *
     * @param string $message
     * @param int $httpCode
     * @param string|null $errorCode
     * @return self
     */
    public static function recordingError(
        string $message,
        int $httpCode = 0,
        ?string $errorCode = null
    ): self {
        return new self("Recording Error: {$message}", $httpCode, $errorCode);
    }

    /**
     * 转换为数组格式
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'error_code' => $this->errorCode,
            'error_details' => $this->errorDetails,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
