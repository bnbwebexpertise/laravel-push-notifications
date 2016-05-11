<?php
/**
 * laravel-push-notifications
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2016 - B&B Web Expertise
 */

namespace Bnb\PushNotifications;

class Device
{

    use Payload;

    const TYPE_APNS = 'apns';
    const TYPE_GCM = 'gcm';

    /**
     * The device type
     *
     * @var string
     */
    public $type;

    /**
     * The push token
     *
     * @var string
     */
    public $token;

    /**
     * The user device ID
     *
     * @var string
     */
    public $uuid;

    /**
     * A hash representing this device signature
     *
     * @var string
     */
    public $hash;


    /**
     * Create an Apple Push Notification Service device
     *
     * @param string $token the push token
     * @param string $uuid  the user device ID
     *
     * @return Device
     */
    public static function apns($token, $uuid = null)
    {
        return new self(self::TYPE_APNS, $token, $uuid);
    }


    /**
     * Create a Google Cloud Messaging device
     *
     * @param string $token the push token
     * @param string $uuid  the user device ID
     *
     * @return $this
     */
    public static function gcm($token, $uuid = null)
    {
        return new self(self::TYPE_GCM, $token, $uuid);
    }


    /**
     * Device constructor.
     *
     * @param string $type
     * @param string $token
     * @param null   $uuid
     */
    private function __construct($type, $token, $uuid = null)
    {
        $this->type  = $type;
        $this->token = $token;
        $this->uuid  = empty($uuid) ? $this->uniqueId() : $uuid;

        $this->hash = md5($this->type . '+' . $this->token);
    }


    public function isGcm()
    {
        return $this->type === self::TYPE_GCM;
    }


    public function isApns()
    {
        return $this->type === self::TYPE_APNS;
    }


    private function uniqueId()
    {
        return rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999);
    }

}