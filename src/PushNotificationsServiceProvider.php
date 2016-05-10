<?php
/**
 * laravel-push-notifications
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2016 - B&B Web Expertise
 */

namespace Bnb\PushNotifications;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class PushNotificationsServiceProvider extends BaseServiceProvider
{

    private $configPath = __DIR__ . '/../config/';


    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if (function_exists('config_path')) {
            $publishPath = config_path('');
        } else {
            $publishPath = base_path('config');
        }

        $this->publishes([
            $this->configPath . '/push.php'                                      => $publishPath . '/push.php',
            $this->configPath . '/push/entrust_root_certification_authority.pem' => $publishPath . '/push/entrust_root_certification_authority.pem',
        ], 'config');
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            $this->configPath . '/push.php', 'push'
        );
    }
}