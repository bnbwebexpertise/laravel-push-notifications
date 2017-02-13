<?php
/**
 * laravel-push-notifications
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2016 - B&B Web Expertise
 */

namespace Bnb\PushNotifications;

use Illuminate\Support\Collection;

class GcmService
{

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var array
     */
    private $errors;

    /**
     * @var array
     */
    private $updates;


    /**
     * GoogleCloudMessaging constructor.
     *
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }


    /**
     * @param Collection $devices
     *
     * @return array
     */
    public function push(Collection $devices)
    {
        if ( ! $devices->count()) {
            return [
                'errors' => [],
                'updates' => []
            ];
        }

        $this->errors = [];
        $this->updates = [];
        $responses = [];

        foreach ($devices as $device) {
            try {
                $response = $this->send($device);

                if ( ! empty ($response)) {
                    $responses [] = $response;
                }
            } catch (\Exception $e) {
                \Log::error(sprintf('PushNotifications::GCM - Failed to send notification in single mode : %s%s%s%sCONTEXT =',
                    $e->getMessage(),
                    PHP_EOL,
                    $e->getTraceAsString(), PHP_EOL), ['device' => $device]);
            }
        }

        foreach ($responses as $response) {
            $data = json_decode($response, true);

            if (isset ($data ['results']) && is_array($data ['results'])) {

                foreach ($data ['results'] as $i => $result) {
                    try {
                        if (isset ($result ['registration_id'])) {
                            $this->triggerDeviceUpdate($devices->get($i), $result ['registration_id']);
                        } elseif (isset ($result ['error'])) {
                            $this->triggerDeviceError($devices->get($i));
                        }
                    } catch (\Exception $e) {
                        \Log::error(sprintf('PushNotifications::GCM - Failed to handle result of notification : %s%s%s%sCONTEXT =',
                            $e->getMessage(), PHP_EOL, $e->getTraceAsString(), PHP_EOL), ['result' => $result]);
                    }
                }
            }
        }

        return [
            'errors' => $this->errors,
            'updates' => $this->updates
        ];
    }


    /**
     * @param Device $device
     */
    protected function triggerDeviceError(Device $device)
    {
        $this->errors[] = $device;
    }


    /**
     * @param Device $device
     * @param string $token
     */
    protected function triggerDeviceUpdate(Device $device, $token)
    {
        $this->updates[] = ['device' => $device, 'token' => $token];
    }


    /**
     * @param Device $device
     *
     * @return string
     */
    private function send(Device $device)
    {
        $headers = [
            "Content-Type:" . "application/json",
            "Authorization:" . "key=" . $this->apiKey
        ];

        $data = [
            'time_to_live' => $device->ttl,
            'data' => [
                'message' => $device->message,
                'title' => $device->title,
                'sound' => $device->sound,
                'appdata' => $device->metadata,
            ],
            'to' => $device->token
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, "https://gcm-http.googleapis.com/gcm/send");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        if ($response === false) {
            $message = sprintf('cURL request failed: (%s) %s', curl_errno($ch), curl_error($ch));

            curl_close($ch);

            throw new \Exception($message);
        }

        curl_close($ch);

        return $response;
    }
}