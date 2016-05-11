<?php
/**
 * laravel-push-notifications
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2016 - B&B Web Expertise
 */

namespace Bnb\PushNotifications;

trait Payload
{

    /**
     * @var string
     */
    public $title;

    /**
     * The
     * @var string
     */
    public $message;

    /**
     * The number to display in the badge
     *
     * @var int
     */
    public $badge;

    /**
     * The name of the sound to play on device
     *
     * @var string
     */
    public $sound = 'default';

    /**
     * The array of custom metadata to send the client
     *
     * @var array
     */
    public $metadata = [];

    /**
     * Time To Live before expiry over network
     *
     * @var int
     */
    public $ttl = 259200;


    /**
     * @param string $value
     *
     * @return $this
     */
    public function title($value)
    {
        $this->title = (string)$value;

        return $this;
    }


    /**
     * @param string $value
     *
     * @return $this
     */
    public function message($value)
    {
        $this->message = (string)$value;

        return $this;
    }


    /**
     * @param int $value
     *
     * @return $this
     */
    public function badge($value)
    {
        $this->badge = (int)$value;

        return $this;
    }


    /**
     * @param string $value
     *
     * @return $this
     */
    public function sound($value)
    {
        $this->sound = (string)$value;

        return $this;
    }


    /**
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function metadata($name, $value)
    {
        $this->metadata[$name] = $value;

        return $this;
    }


    /**
     * @param int $value
     *
     * @return $this
     */
    public function ttl($value)
    {
        $this->ttl = (int)$value;

        return $this;
    }


    /**
     * Merge current payload into the given one.
     * Takes all non empty values from parameter and override the local ones.
     *
     * @param mixed $payload
     *
     * @return mixed The given payload
     */
    public function merge($payload)
    {
        if (empty($payload->title)) {
            $payload->title = $this->title;
        }

        if (empty($payload->message)) {
            $payload->message = $this->message;
        }

        if (empty($payload->sound)) {
            $payload->sound = $this->sound;
        }

        if ($payload->badge <= 0) {
            $payload->badge = $this->badge;
        }

        if (is_array($payload->metadata) && is_array($this->metadata)) {
            $payload->metadata = array_merge($this->metadata, $payload->metadata);
        }

        if ($payload->ttl <= 0) {
            $payload->ttl = $this->ttl;
        }

        return $payload;
    }
}