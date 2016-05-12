<?php
/**
 * laravel-push-notifications
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2016 - B&B Web Expertise
 */

namespace Bnb\PushNotifications;

use ApnsPHP_Abstract;
use ApnsPHP_Message;
use ApnsPHP_Push;
use Illuminate\Support\Collection;

class ApnsService
{

    /**
     * @var string
     */
    private $certificate;

    /**
     * @var string
     */
    private $passPhrase;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var int
     */
    private $seq = 0;

    /**
     * @var array
     */
    private $errors;


    /**
     * AppleNotificationService constructor.
     *
     * @param string $certificate
     * @param string $passPhrase
     * @param string $environment
     */
    public function __construct($certificate, $passPhrase, $environment = 'production')
    {
        $this->certificate = $certificate;
        $this->passPhrase  = $passPhrase;
        $this->environment = $environment;
    }


    /**
     * @param Collection $devices list of devices to push to with payload
     *
     * @return array
     */
    public function push(Collection $devices)
    {
        if ($devices->count() <= 0) {
            return;
        }

        $this->errors = [];
        $environment  = ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION;

        if ('development' === $this->environment) {
            $environment = ApnsPHP_Abstract::ENVIRONMENT_SANDBOX;
        }

        $apns = new ApnsPHP_Push ($environment, $this->certificate);
        $apns->setRootCertificationAuthority(config('push.apns.root'));
        $apns->setLogger(new ApnsLogWriter);
        $apns->connect();

        foreach ($devices as $device) {
            /** @var Device $device */
            try {
                $properties = join(',', array_merge(['title', 'message'], array_keys($device->metadata)));
                $message    = new ApnsPHP_Message ($device->token);

                $message->setCustomIdentifier($device->hash . '::' . (++$this->seq));
                $message->setText($device->message);
                $message->setSound($device->sound || 'default');
                $message->setExpiry($device->ttl);
                $message->setCustomProperty('title', $device->title);
                $message->setCustomProperty('message', $device->message);

                foreach ($device->metadata as $name => $value) {
                    $message->setCustomProperty($name, $value);
                }

                $message->setCustomProperty('props', $properties);

                if ( ! empty($device->badge)) {
                    $message->setBadge($device->badge);
                }

                $apns->add($message);
            } catch (\Exception $e) {
                \Log::error(sprintf('PushNotifications::APNs - Failed to send notification : %s%s%s%sCONTEXT =',
                    $e->getMessage(), PHP_EOL,
                    $e->getTraceAsString(), PHP_EOL), ['message' => $device]);
            }
        }

        if (count($apns->getQueue(false)) > 0) {
            $apns->send();
        }

        $apns->disconnect();

        $aErrorQueue = $apns->getErrors();

        if ( ! empty ($aErrorQueue)) {
            foreach ($aErrorQueue as $msgId => $error) {
                $foundError = false;

                if (isset($error ['ERRORS']) && is_array($error ['ERRORS']) && 0 < count($error ['ERRORS'])) {
                    foreach ($error ['ERRORS'] as $e) {
                        if (isset($e['statusCode'])) {
                            if (8 == $e ['statusCode']) {
                                $this->triggerDeviceError($devices, $error['MESSAGE']->getRecipient());
                            } elseif (0 != $e ['statusCode']) {
                                $foundError = true;
                            }
                        }
                    }
                }

                if ($foundError) {
                    \Log::error('PushNotifications::APNs - error', $error);
                }
            }
        }

        $apns = null;

        return [
            'errors'  => $this->errors,
            'updates' => []
        ];
    }


    /**
     * @param string $token
     */
    protected function triggerDeviceError(Collection $devices, $token)
    {
        $device = $devices->search(function ($device) use ($token) {
            return $device->token == $token;
        });

        if ( ! empty($device)) {
            $this->errors[] = $device;
        }
    }
}