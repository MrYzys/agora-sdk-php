<?php

namespace Agora\RtcSdk\Http;

use Agora\RtcSdk\Config\AgoraConfig;
use Agora\RtcSdk\Exceptions\AgoraException;

/**
 * HTTP客户端类
 *
 * @package Agora\RtcSdk\Http
 */
class HttpClient
{
    /**
     * Agora配置
     *
     * @var AgoraConfig
     */
    private $config;

    /**
     * 请求超时时间（秒）
     *
     * @var int
     */
    private $timeout = 30;

    /**
     * 构造函数
     *
     * @param AgoraConfig $config
     */
    public function __construct(AgoraConfig $config)
    {
        $this->config = $config;
    }

    /**
     * 设置请求超时时间
     *
     * @param int $timeout
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * 发送GET请求
     *
     * @param string $endpoint
     * @param array $headers
     * @return array
     * @throws AgoraException
     */
    public function get(string $endpoint, array $headers = []): array
    {
        return $this->request('GET', $endpoint, null, $headers);
    }

    /**
     * 发送POST请求
     *
     * @param string $endpoint
     * @param array|null $data
     * @param array $headers
     * @return array
     * @throws AgoraException
     */
    public function post(string $endpoint, ?array $data = null, array $headers = []): array
    {
        return $this->request('POST', $endpoint, $data, $headers);
    }

    /**
     * 发送PUT请求
     *
     * @param string $endpoint
     * @param array|null $data
     * @param array $headers
     * @return array
     * @throws AgoraException
     */
    public function put(string $endpoint, ?array $data = null, array $headers = []): array
    {
        return $this->request('PUT', $endpoint, $data, $headers);
    }

    /**
     * 发送DELETE请求
     *
     * @param string $endpoint
     * @param array $headers
     * @return array
     * @throws AgoraException
     */
    public function delete(string $endpoint, array $headers = []): array
    {
        return $this->request('DELETE', $endpoint, null, $headers);
    }

    /**
     * 发送HTTP请求
     *
     * @param string $method
     * @param string $endpoint
     * @param array|null $data
     * @param array $headers
     * @return array
     * @throws AgoraException
     */
    private function request(string $method, string $endpoint, ?array $data = null, array $headers = []): array
    {
        $url = $this->config->getApiBaseUrl() . '/' . ltrim($endpoint, '/');

        $ch = curl_init();

        // 设置基本选项
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ]);

        // 设置请求头
        $defaultHeaders = [
            'Content-Type: application/json',
            'User-Agent: Agora-RTC-PHP-SDK/1.0.0',
        ];

        // 添加认证头
        if ($this->config->isRestfulApiConfigValid()) {
            $defaultHeaders[] = 'Authorization: ' . $this->config->getBasicAuthHeader();
        }

        $allHeaders = array_merge($defaultHeaders, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);

        // 设置请求体
        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // 执行请求
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        // 检查cURL错误
        if ($error) {
            throw AgoraException::apiError("cURL Error: {$error}", 0);
        }

        // 解析响应
        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw AgoraException::apiError(
                "Invalid JSON response: " . json_last_error_msg(),
                $httpCode
            );
        }

        // 检查HTTP状态码
        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['message'] ?? 'Unknown error';
            $errorCode = $decodedResponse['code'] ?? null;

            throw AgoraException::apiError(
                $errorMessage,
                $httpCode,
                $errorCode,
                $decodedResponse
            );
        }

        return $decodedResponse;
    }
}
