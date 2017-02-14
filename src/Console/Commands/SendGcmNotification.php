<?php
/**
 * laravel-push-notifications
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2017 - B&B Web Expertise
 */

namespace Bnb\PushNotifications\Console\Commands;

use Bnb\PushNotifications\Device;
use Bnb\PushNotifications\Notification;
use Illuminate\Console\Command;

class SendGcmNotification extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:gcm
                                {token : the device token}
                                {message : the notification message}
                                {title? : (optional) the notification title}
                                {--sender-id= : The GCM sender ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a push notification to a GCM device';


    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        config([
            'push' => [
                'apns' => config('push.apns'),
                'gcm' => [
                    'key' => $this->option('sender-id') ?: config('push.gcm.key'),
                ],
            ],
        ]);

        $notification = new Notification($this->argument('title') ?: 'Notification test', $this->argument('message'));

        $notification->push(Device::gcm($this->argument('token')));

        $results = $notification->send();

        foreach ($results['errors'] as $data) {
            $this->error(sprintf('Device %s has failed', $data->token));
        }

        foreach ($results['updates'] as $data) {
            $this->error(sprintf('Device %s has been updated', $data->token));
        }
    }
}
