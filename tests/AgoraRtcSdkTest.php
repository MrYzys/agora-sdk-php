<?php

namespace Agora\RtcSdk\Tests;

use PHPUnit\Framework\TestCase;
use Agora\RtcSdk\AgoraRtcSdk;
use Agora\RtcSdk\Config\AgoraConfig;
use Agora\RtcSdk\TokenBuilder\RtcTokenBuilder2;
use Agora\RtcSdk\Exceptions\AgoraException;

/**
 * Agora RTC SDK 测试类
 */
class AgoraRtcSdkTest extends TestCase
{
    private $appId = 'test_app_id';
    private $appCertificate = 'test_app_certificate';
    private $customerId = 'test_customer_id';
    private $customerSecret = 'test_customer_secret';

    /**
     * 测试SDK创建
     */
    public function testCreateSdk()
    {
        $sdk = AgoraRtcSdk::create($this->appId, $this->appCertificate);
        
        $this->assertInstanceOf(AgoraRtcSdk::class, $sdk);
        $this->assertEquals($this->appId, $sdk->getConfig()->getAppId());
        $this->assertEquals($this->appCertificate, $sdk->getConfig()->getAppCertificate());
    }

    /**
     * 测试完整配置SDK创建
     */
    public function testCreateSdkWithFullConfig()
    {
        $sdk = AgoraRtcSdk::create(
            $this->appId,
            $this->appCertificate,
            $this->customerId,
            $this->customerSecret
        );
        
        $config = $sdk->getConfig();
        $this->assertEquals($this->customerId, $config->getCustomerId());
        $this->assertEquals($this->customerSecret, $config->getCustomerSecret());
        $this->assertTrue($config->isRestfulApiConfigValid());
    }

    /**
     * 测试无效配置
     */
    public function testInvalidConfig()
    {
        $this->expectException(AgoraException::class);
        $this->expectExceptionMessage('Invalid Agora configuration');
        
        AgoraRtcSdk::create('', '');
    }

    /**
     * 测试Token生成
     */
    public function testGenerateToken()
    {
        $sdk = AgoraRtcSdk::create($this->appId, $this->appCertificate);
        
        $token = $sdk->generateToken('test_channel', 12345);
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /**
     * 测试用户Token生成
     */
    public function testGenerateUserToken()
    {
        $sdk = AgoraRtcSdk::create($this->appId, $this->appCertificate);
        
        $userToken = $sdk->generateUserToken('test_channel', 12345, true, 3600);
        
        $this->assertIsArray($userToken);
        $this->assertEquals('test_channel', $userToken['channel_name']);
        $this->assertEquals(12345, $userToken['uid']);
        $this->assertTrue($userToken['is_publisher']);
        $this->assertEquals(RtcTokenBuilder2::ROLE_PUBLISHER, $userToken['role']);
        $this->assertNotEmpty($userToken['token']);
    }

    /**
     * 测试房间创建
     */
    public function testCreateRoom()
    {
        $sdk = AgoraRtcSdk::create($this->appId, $this->appCertificate);
        
        $room = $sdk->createRoom('test_channel');
        
        $this->assertIsArray($room);
        $this->assertEquals('test_channel', $room['channel_name']);
        $this->assertEquals(1, $room['admin_uid']);
        $this->assertNotEmpty($room['admin_token']);
        $this->assertArrayHasKey('created_at', $room);
    }

    /**
     * 测试无效频道名称
     */
    public function testInvalidChannelName()
    {
        $sdk = AgoraRtcSdk::create($this->appId, $this->appCertificate);
        
        $this->expectException(AgoraException::class);
        $this->expectExceptionMessage('Invalid channel name format');
        
        $sdk->createRoom(''); // 空频道名称
    }

    /**
     * 测试频道名称验证
     */
    public function testChannelNameValidation()
    {
        // 有效的频道名称
        $this->assertTrue(RtcTokenBuilder2::isValidChannelName('test_channel'));
        $this->assertTrue(RtcTokenBuilder2::isValidChannelName('channel123'));
        $this->assertTrue(RtcTokenBuilder2::isValidChannelName('test-channel_01'));
        
        // 无效的频道名称
        $this->assertFalse(RtcTokenBuilder2::isValidChannelName(''));
        $this->assertFalse(RtcTokenBuilder2::isValidChannelName(str_repeat('a', 65))); // 超过64字符
    }

    /**
     * 测试Token角色
     */
    public function testTokenRoles()
    {
        $sdk = AgoraRtcSdk::create($this->appId, $this->appCertificate);
        
        // 发布者Token
        $publisherToken = $sdk->generateUserToken('test_channel', 12345, true);
        $this->assertEquals(RtcTokenBuilder2::ROLE_PUBLISHER, $publisherToken['role']);
        $this->assertTrue($publisherToken['is_publisher']);
        
        // 订阅者Token
        $subscriberToken = $sdk->generateUserToken('test_channel', 12346, false);
        $this->assertEquals(RtcTokenBuilder2::ROLE_SUBSCRIBER, $subscriberToken['role']);
        $this->assertFalse($subscriberToken['is_publisher']);
    }

    /**
     * 测试详细权限Token生成
     */
    public function testGenerateTokenWithPrivileges()
    {
        $sdk = AgoraRtcSdk::create($this->appId, $this->appCertificate);
        
        $token = $sdk->generateTokenWithPrivileges(
            'test_channel',
            12345,
            3600,  // Token过期时间
            3600,  // 加入频道权限
            3600,  // 发布音频权限
            3600,  // 发布视频权限
            0      // 发布数据流权限
        );
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /**
     * 测试SDK版本
     */
    public function testSdkVersion()
    {
        $version = AgoraRtcSdk::getVersion();
        
        $this->assertIsString($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version);
    }

    /**
     * 测试云录制客户端获取（无完整配置）
     */
    public function testGetCloudRecordingWithoutFullConfig()
    {
        $sdk = AgoraRtcSdk::create($this->appId, $this->appCertificate);
        
        $this->expectException(AgoraException::class);
        $this->expectExceptionMessage('Cloud recording requires Customer ID and Customer Secret');
        
        $sdk->getCloudRecording();
    }

    /**
     * 测试事件解析器获取（无完整配置）
     */
    public function testGetEventParserWithoutFullConfig()
    {
        $sdk = AgoraRtcSdk::create($this->appId, $this->appCertificate);
        
        $this->expectException(AgoraException::class);
        $this->expectExceptionMessage('Event parser requires Customer Secret for signature verification');
        
        $sdk->getEventParser();
    }

    /**
     * 测试配置验证
     */
    public function testConfigValidation()
    {
        // 基础配置验证
        $basicConfig = new AgoraConfig($this->appId, $this->appCertificate);
        $this->assertTrue($basicConfig->isValid());
        $this->assertFalse($basicConfig->isRestfulApiConfigValid());
        
        // 完整配置验证
        $fullConfig = new AgoraConfig(
            $this->appId,
            $this->appCertificate,
            $this->customerId,
            $this->customerSecret
        );
        $this->assertTrue($fullConfig->isValid());
        $this->assertTrue($fullConfig->isRestfulApiConfigValid());
        
        // 无效配置
        $invalidConfig = new AgoraConfig('', '');
        $this->assertFalse($invalidConfig->isValid());
        $this->assertFalse($invalidConfig->isRestfulApiConfigValid());
    }

    /**
     * 测试Token参数验证
     */
    public function testTokenParameterValidation()
    {
        $sdk = AgoraRtcSdk::create($this->appId, $this->appCertificate);
        
        // 测试无效的过期时间
        $this->expectException(AgoraException::class);
        
        $sdk->generateToken('test_channel', 12345, RtcTokenBuilder2::ROLE_PUBLISHER, 0);
    }

    /**
     * 测试房间选项
     */
    public function testCreateRoomWithOptions()
    {
        $sdk = AgoraRtcSdk::create($this->appId, $this->appCertificate);
        
        $options = [
            'admin_uid' => 999,
            'token_expire_time' => 7200,
        ];
        
        $room = $sdk->createRoom('test_channel', $options);
        
        $this->assertEquals(999, $room['admin_uid']);
        $this->assertEquals(7200, $room['token_expire_time']);
    }
}
