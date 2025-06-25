<?php

namespace Agora\RtcSdk\Config;

/**
 * Agora配置类
 *
 * @package Agora\RtcSdk\Config
 */
class AgoraConfig
{
    /**
     * App ID
     *
     * @var string
     */
    private $appId;

    /**
     * App Certificate
     *
     * @var string
     */
    private $appCertificate;

    /**
     * Customer ID (用于RESTful API认证)
     *
     * @var string
     */
    private $customerId;

    /**
     * Customer Secret (用于RESTful API认证)
     *
     * @var string
     */
    private $customerSecret;

    /**
     * API基础URL
     *
     * @var string
     */
    private $apiBaseUrl = 'https://api.agora.io';

    /**
     * 构造函数
     *
     * @param string $appId App ID
     * @param string $appCertificate App Certificate
     * @param string $customerId Customer ID
     * @param string $customerSecret Customer Secret
     */
    public function __construct(
        string $appId,
        string $appCertificate,
        string $customerId = '',
        string $customerSecret = ''
    ) {
        $this->appId = $appId;
        $this->appCertificate = $appCertificate;
        $this->customerId = $customerId;
        $this->customerSecret = $customerSecret;
    }

    /**
     * 获取App ID
     *
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * 获取App Certificate
     *
     * @return string
     */
    public function getAppCertificate(): string
    {
        return $this->appCertificate;
    }

    /**
     * 获取Customer ID
     *
     * @return string
     */
    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    /**
     * 获取Customer Secret
     *
     * @return string
     */
    public function getCustomerSecret(): string
    {
        return $this->customerSecret;
    }

    /**
     * 获取API基础URL
     *
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return $this->apiBaseUrl;
    }

    /**
     * 设置API基础URL
     *
     * @param string $apiBaseUrl
     * @return self
     */
    public function setApiBaseUrl(string $apiBaseUrl): self
    {
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
        return $this;
    }

    /**
     * 获取基础认证头
     *
     * @return string
     */
    public function getBasicAuthHeader(): string
    {
        $credentials = $this->customerId . ':' . $this->customerSecret;
        return 'Basic ' . base64_encode($credentials);
    }

    /**
     * 验证配置是否完整
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->appId) && !empty($this->appCertificate);
    }

    /**
     * 验证RESTful API配置是否完整
     *
     * @return bool
     */
    public function isRestfulApiConfigValid(): bool
    {
        return $this->isValid() && !empty($this->customerId) && !empty($this->customerSecret);
    }
}
