<?php
/**
 * laravel-push-notifications
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2016 - B&B Web Expertise
 */

namespace Bnb\PushNotifications;

use Illuminate\Support\Collection;

class Notification
{

    use Payload;

    /** @var  Collection */
    protected $devices;

    protected $gcmOptions = [
        'key' => null
    ];

    protected $apnsOptions = [
        'certificate' => null,
        'password' => null,
        'environment' => null,
    ];


    /**
     * Class constructor
     *
     * @param string $title
     * @param string $message
     */
    public function __construct($title, $message)
    {
        $this->title = $title;
        $this->message = $message;
        $this->devices = new Collection;

        $this->gcmOptions['key'] = config('push.gcm.key');

        $this->apnsOptions['certificate'] = base_path(config('push.apns.certificate'));
        $this->apnsOptions['password'] = config('push.apns.password');
        $this->apnsOptions['environment'] = config('push.apns.environment');
    }


    /**
     * Add a device to the push notification
     *
     * @param Device $device
     *
     * @return $this
     */
    public function push(Device $device)
    {
        $this->devices = $this->devices->push($device)->unique('hash');

        return $this;
    }


    /**
     * Get the number of devices
     *
     * @return int
     */
    public function count()
    {
        return $this->devices->count();
    }


    /**
     * Send the notification to devices.
     * Returns errors (APNs and GCM) and updated token (GCM).
     *
     * @return array [ 'errors': [ Device, ... ], 'updates' => [ [ 'device' => Device, 'token' => 'a-new-token' ], ... ] ]
     */
    public function send()
    {
        $apns = new Collection;
        $gcm = new Collection;

        $this->devices->each(function (Device $device) use (&$apns, &$gcm) {
            if ($device->isApns()) {
                $apns->push($this->merge($device));
            } elseif ($device->isGcm()) {
                $gcm->push($this->merge($device));
            }
        });

        $results = [
            'errors' => [],
            'updates' => [],
            'payloads' => []
        ];

        $this->mergeResults($results, $this->pushApns($apns));
        $this->mergeResults($results, $this->pushGcm($gcm));

        // Reset
        $apns = null;
        $gcm = null;
        $this->device = new Collection;

        return $results;
    }


    /**
     * Send to APNs devices
     *
     * @param Collection $devices
     *
     * @return array
     * @throws \Exception
     */
    protected function pushApns(Collection $devices)
    {
        if (empty($devices) || $devices->isEmpty()) {
            return [
                'errors' => [],
                'updates' => [],
                'payloads' => []
            ];
        }

        $certificate = $this->apnsOptions['certificate'];

        if ( ! file_exists($certificate) || is_dir($certificate)) {
            throw new \Exception('APNs certificate does not exists or is not a valid file at location: ' . $certificate);
        }

        $service = new ApnsService(
            $certificate,
            $this->apnsOptions['password'],
            $this->apnsOptions['environment']
        );

        return $this->sendToService($devices, $service);
    }


    /**
     * Send to GCM devices
     *
     * @param Collection $devices
     *
     * @return array
     */
    private function pushGcm(Collection $devices)
    {
        if (empty($devices) || $devices->isEmpty()) {
            return [
                'errors' => [],
                'updates' => [],
                'payloads' => []
            ];
        }

        $service = new GcmService($this->gcmOptions['key']);

        return $this->sendToService($devices, $service);
    }


    /**
     * @param Collection             $devices
     * @param ApnsService|GcmService $service
     *
     * @return array
     */
    private function sendToService(Collection $devices, $service)
    {
        /** @var Collection $chunks */
        $chunks = $devices->chunk((int)config('push.chunk', 100));

        $results = [
            'errors' => [],
            'updates' => [],
            'payloads' => []
        ];

        foreach ($chunks as $chunk) {
            $this->mergeResults($results, $service->push($chunk));
        }

        return $results;
    }


    /**
     * Merge a service result into the global accumulator
     *
     * @param array $results
     * @param array $result
     */
    private function mergeResults(&$results, $result)
    {
        $results['errors'] = array_merge($results['errors'], $result['errors']);
        $results['updates'] = array_merge($results['updates'], $result['updates']);

        if (config('push.payloads')) {
            $results['payloads'] = array_merge($results['payloads'], $result['payloads']);
        }
    }


    /**
     * Gets a GCM option value
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getGcmOption($key)
    {
        return isset($this->gcmOptions[$key]) ? $this->gcmOptions[$key] : null;
    }


    /**
     * Gets an APNs option value
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getApnsOption($key)
    {
        return isset($this->apnsOptions[$key]) ? $this->apnsOptions[$key] : null;
    }


    /**
     * Sets a GCM option value
     *
     * @param string $key
     * @param string $value
     *
     * @return self
     */
    public function setGcmOption($key, $value)
    {
        if (array_key_exists($key, $this->gcmOptions)) {
            $this->gcmOptions[$key] = $value;
        }

        return $this;
    }


    /**
     * Sets an APNs option value
     *
     * @param string $key
     * @param string $value
     *
     * @return self
     */
    public function setApnsOption($key, $value)
    {
        if (array_key_exists($key, $this->apnsOptions)) {
            $this->apnsOptions[$key] = $value;
        }

        return $this;
    }
}