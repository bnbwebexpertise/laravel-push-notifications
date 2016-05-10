<?php
use Bnb\PushNotifications\Device;
use Bnb\PushNotifications\Notification;

/**
 * laravel-push-notifications
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2016 - B&B Web Expertise
 */
class NotificationTest extends PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function it_builds_a_blank_notification()
    {
        $notification = new Notification('title', 'message');

        $this->assertEquals('title', $notification->title);
        $this->assertEquals('message', $notification->message);
    }


    /**
     * @test
     */
    public function it_builds_a_blank_notification_with_chaining()
    {
        $notification = new Notification('title', 'message');
        $notification
            ->badge(5)
            ->sound('sound')
            ->ttl(1234)
            ->metadata('key1', 'value1')
            ->metadata('key2', 'value2');

        $this->assertEquals('title', $notification->title);
        $this->assertEquals('message', $notification->message);
        $this->assertEquals(5, $notification->badge);
        $this->assertEquals('sound', $notification->sound);
        $this->assertEquals(1234, $notification->ttl);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $notification->metadata);
    }


    /**
     * @test
     */
    public function it_builds_a_notification_with_unique_devices()
    {
        $notification = new Notification('title', 'message');
        // Should be added
        $notification->push(Device::gcm('token', 'uuid'));
        $notification->push(Device::apns('token', 'uuid'));
        // Should not be added as per unique hash rule
        $notification->push(Device::gcm('token', 'uuid'));
        $notification->push(Device::apns('token', 'uuid'));

        $this->assertEquals('title', $notification->title);
        $this->assertEquals('message', $notification->message);
        $this->assertEquals(2, $notification->count());
    }


    /**
     * @test
     */
    public function it_merges_device_into_notification()
    {
        $notification = new Notification('title', 'message');
        $notification
            ->badge(5)
            ->sound('sound')
            ->ttl(1234)
            ->metadata('key1', 'value1')
            ->metadata('key2', 'value2');

        $device = Device::apns('apns', 'uuid');
        $device
            ->title('deviceTitle')
            ->message('deviceMessage')
            ->badge(10)
            ->sound('deviceSound')
            ->ttl(4321)
            ->metadata('key1', 'deviceValue1')
            ->metadata('deviceKey2', 'value2');

        $payload = $notification->merge($device);

        $this->assertEquals('deviceTitle', $payload->title);
        $this->assertEquals('deviceMessage', $payload->message);
        $this->assertEquals(10, $payload->badge);
        $this->assertEquals('deviceSound', $payload->sound);
        $this->assertEquals(4321, $payload->ttl);
        $this->assertEquals(['key1' => 'deviceValue1', 'key2' => 'value2', 'deviceKey2' => 'value2'],
            $payload->metadata);
    }
}