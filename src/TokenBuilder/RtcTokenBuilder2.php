<?php

namespace Agora\Sdk\TokenBuilder;

use Agora\Sdk\Exceptions\AgoraException;

/**
 * RTC Token Builder 2 - 基于Agora AccessToken2
 *
 * @package Agora\Sdk\TokenBuilder
 */
class RtcTokenBuilder2
{
    /**
     * 用户角色：发布者
     */
    const ROLE_PUBLISHER = 1;

    /**
     * 用户角色：订阅者
     */
    const ROLE_SUBSCRIBER = 2;

    /**
     * 服务类型：RTC
     */
    const SERVICE_TYPE_RTC = 1;

    /**
     * 权限类型：加入频道
     */
    const PRIVILEGE_JOIN_CHANNEL = 1;

    /**
     * 权限类型：发布音频流
     */
    const PRIVILEGE_PUBLISH_AUDIO_STREAM = 2;

    /**
     * 权限类型：发布视频流
     */
    const PRIVILEGE_PUBLISH_VIDEO_STREAM = 3;

    /**
     * 权限类型：发布数据流
     */
    const PRIVILEGE_PUBLISH_DATA_STREAM = 4;

    /**
     * 生成RTC Token (方法1：统一过期时间)
     *
     * @param string $appId App ID
     * @param string $appCertificate App Certificate
     * @param string $channelName 频道名称
     * @param int $uid 用户ID (0表示任意用户ID)
     * @param int $role 用户角色 (1=发布者, 2=订阅者)
     * @param int $tokenExpire Token过期时间（秒）
     * @param int $privilegeExpire 权限过期时间（秒，0表示永不过期）
     * @return string
     * @throws AgoraException
     */
    public static function buildTokenWithUid(
        string $appId,
        string $appCertificate,
        string $channelName,
        int $uid,
        int $role,
        int $tokenExpire,
        int $privilegeExpire = 0
    ): string {
        if (empty($appId)) {
            throw AgoraException::tokenError('App ID cannot be empty');
        }

        if (empty($appCertificate)) {
            throw AgoraException::tokenError('App Certificate cannot be empty');
        }

        if (empty($channelName)) {
            throw AgoraException::tokenError('Channel name cannot be empty');
        }

        if (!in_array($role, [self::ROLE_PUBLISHER, self::ROLE_SUBSCRIBER])) {
            throw AgoraException::tokenError('Invalid role. Must be 1 (publisher) or 2 (subscriber)');
        }

        if ($tokenExpire <= 0) {
            throw AgoraException::tokenError('Token expire time must be greater than 0');
        }

        // 构建权限映射
        $privileges = [];

        // 加入频道权限
        $privileges[self::PRIVILEGE_JOIN_CHANNEL] = $privilegeExpire;

        // 根据角色设置权限
        if ($role === self::ROLE_PUBLISHER) {
            $privileges[self::PRIVILEGE_PUBLISH_AUDIO_STREAM] = $privilegeExpire;
            $privileges[self::PRIVILEGE_PUBLISH_VIDEO_STREAM] = $privilegeExpire;
            $privileges[self::PRIVILEGE_PUBLISH_DATA_STREAM] = $privilegeExpire;
        }

        return self::generateToken(
            $appId,
            $appCertificate,
            $channelName,
            $uid,
            $tokenExpire,
            $privileges
        );
    }

    /**
     * 生成RTC Token (方法2：分别设置权限过期时间)
     *
     * @param string $appId App ID
     * @param string $appCertificate App Certificate
     * @param string $channelName 频道名称
     * @param int $uid 用户ID
     * @param int $tokenExpire Token过期时间（秒）
     * @param int $joinChannelPrivilegeExpire 加入频道权限过期时间（秒）
     * @param int $pubAudioPrivilegeExpire 发布音频权限过期时间（秒）
     * @param int $pubVideoPrivilegeExpire 发布视频权限过期时间（秒）
     * @param int $pubDataStreamPrivilegeExpire 发布数据流权限过期时间（秒）
     * @return string
     * @throws AgoraException
     */
    public static function buildTokenWithUidAndPrivilege(
        string $appId,
        string $appCertificate,
        string $channelName,
        int $uid,
        int $tokenExpire,
        int $joinChannelPrivilegeExpire = 0,
        int $pubAudioPrivilegeExpire = 0,
        int $pubVideoPrivilegeExpire = 0,
        int $pubDataStreamPrivilegeExpire = 0
    ): string {
        if (empty($appId)) {
            throw AgoraException::tokenError('App ID cannot be empty');
        }

        if (empty($appCertificate)) {
            throw AgoraException::tokenError('App Certificate cannot be empty');
        }

        if (empty($channelName)) {
            throw AgoraException::tokenError('Channel name cannot be empty');
        }

        if ($tokenExpire <= 0) {
            throw AgoraException::tokenError('Token expire time must be greater than 0');
        }

        $privileges = [
            self::PRIVILEGE_JOIN_CHANNEL => $joinChannelPrivilegeExpire,
            self::PRIVILEGE_PUBLISH_AUDIO_STREAM => $pubAudioPrivilegeExpire,
            self::PRIVILEGE_PUBLISH_VIDEO_STREAM => $pubVideoPrivilegeExpire,
            self::PRIVILEGE_PUBLISH_DATA_STREAM => $pubDataStreamPrivilegeExpire,
        ];

        return self::generateToken(
            $appId,
            $appCertificate,
            $channelName,
            $uid,
            $tokenExpire,
            $privileges
        );
    }

    /**
     * 生成Token的核心方法
     *
     * @param string $appId
     * @param string $appCertificate
     * @param string $channelName
     * @param int $uid
     * @param int $tokenExpire
     * @param array $privileges
     * @return string
     */
    private static function generateToken(
        string $appId,
        string $appCertificate,
        string $channelName,
        int $uid,
        int $tokenExpire,
        array $privileges
    ): string {
        $currentTimestamp = time();
        $expireTimestamp = $currentTimestamp + $tokenExpire;

        // 构建消息体
        $message = [
            'salt' => random_int(1, 99999999),
            'ts' => $currentTimestamp,
            'privileges' => $privileges,
        ];

        // 构建Token内容
        $tokenContent = [
            'iss' => $appId,
            'exp' => $expireTimestamp,
        ];

        // 添加频道和用户信息
        if (!empty($channelName)) {
            $tokenContent['channel'] = $channelName;
        }

        if ($uid > 0) {
            $tokenContent['uid'] = $uid;
        }

        // 添加消息
        $tokenContent['msg'] = base64_encode(json_encode($message));

        // 生成签名
        $signature = self::generateSignature($appCertificate, json_encode($tokenContent));

        // 构建最终Token
        $token = base64_encode(json_encode([
            'signature' => $signature,
            'content' => $tokenContent,
        ]));

        return $token;
    }

    /**
     * 生成HMAC-SHA256签名
     *
     * @param string $appCertificate
     * @param string $content
     * @return string
     */
    private static function generateSignature(string $appCertificate, string $content): string
    {
        return hash_hmac('sha256', $content, $appCertificate);
    }

    /**
     * 验证频道名称格式
     *
     * @param string $channelName
     * @return bool
     */
    public static function isValidChannelName(string $channelName): bool
    {
        if (empty($channelName) || strlen($channelName) > 64) {
            return false;
        }

        // 检查字符集：a-z, A-Z, 0-9, 空格, 以及特定符号（不包括@）
        return preg_match('/^[a-zA-Z0-9 !#$%&()+\-:;<=>?\[\]^_`{|}~,]+$/', $channelName);
    }
}
