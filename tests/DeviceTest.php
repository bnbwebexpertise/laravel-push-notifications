<?php
use Bnb\PushNotifications\Device;

/**
 * laravel-push-notifications
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2016 - B&B Web Expertise
 */
class DeviceTest extends PHPUnit_Framework_TestCase
{

    const ANDROID_TOKEN = 'AIaeRtYiUoP-QsDfghQJK1lMWXCvBN23AZE4RT6u';
    const APPLE_TOKEN = '123e4567-e89b-12d3-a456-42665544000';
    const UUID = 'a0b5822603a5eb4711a663d193e71feb';


    /**
     * @test
     */
    public function it_builds_a_simple_android_device()
    {
        $device = Device::gcm(self::ANDROID_TOKEN, self::UUID);

        $this->assertEquals(self::ANDROID_TOKEN, $device->token);
        $this->assertEquals(self::UUID, $device->uuid);
        $this->assertTrue($device->isGcm());
        $this->assertFalse($device->isApns());
    }


    /**
     * @test
     */
    public function it_builds_a_simple_apple_device()
    {
        $device = Device::apns(self::APPLE_TOKEN, self::UUID);

        $this->assertEquals(self::APPLE_TOKEN, $device->token);
        $this->assertEquals(self::UUID, $device->uuid);
        $this->assertFalse($device->isGcm());
        $this->assertTrue($device->isApns());
    }


    /**
     * @test
     */
    public function it_builds_a_complex_apple_device_with_chaining()
    {
        $device = Device::apns(self::APPLE_TOKEN, self::UUID);
        $device
            ->title('title')
            ->message('message')
            ->badge(5)
            ->sound('sound')
            ->ttl(1234)
            ->metadata('key1', 'value1')
            ->metadata('key2', 'value2');

        $this->assertEquals(self::APPLE_TOKEN, $device->token);
        $this->assertEquals(self::UUID, $device->uuid);
        $this->assertFalse($device->isGcm());
        $this->assertTrue($device->isApns());

        $this->assertEquals('title', $device->title);
        $this->assertEquals('message', $device->message);
        $this->assertEquals(5, $device->badge);
        $this->assertEquals('sound', $device->sound);
        $this->assertEquals(1234, $device->ttl);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $device->metadata);
    }
}