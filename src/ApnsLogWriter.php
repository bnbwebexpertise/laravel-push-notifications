<?php
/**
 * laravel-push-notifications
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2016 - B&B Web Expertise
 */

namespace Bnb\PushNotifications;

use ApnsPHP_Log_Interface;

class ApnsLogWriter implements ApnsPHP_Log_Interface
{

    /**
     * Logs a message.
     *
     * @param  $sMessage @type string The message.
     */
    public function log($sMessage)
    {
        \Log::info('PushNotifications::APNs - ' . $sMessage);
    }
}