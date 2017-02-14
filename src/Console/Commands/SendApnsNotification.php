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

class SendApnsNotification extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:apns
                                {token : the device token}
                                {message : the notification message}
                                {title? : (optional) the notification title}
                                {--certificate= : The Apple certificate path}
                                {--password= : The Apple certificate password}
                                {--environment= : The Apple push environment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a push notification to an APNs device';


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
                'apns' => [
                    'root' => config('push.apns.root'),
                    'certificate' => $this->option('certificate') ?: config('push.apns.certificate'),
                    'password' => $this->option('password') ?: config('push.apns.password'),
                    'environment' => $this->option('environment') ?: config('push.apns.environment'),
                ],
                'gcm' => config('push.gcm'),
            ],
        ]);

        $notification = new Notification($this->argument('title') ?: 'Notification test', $this->argument('message'));

        $notification->push(Device::apns($this->argument('token')));

        $results = $notification->send();

        foreach ($results['errors'] as $data) {
            $this->error(sprintf('Device %s has failed', $data->token));
        }

        foreach ($results['updates'] as $data) {
            $this->error(sprintf('Device %s has been updated', $data->token));
        }
    }
}
