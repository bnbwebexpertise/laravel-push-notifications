# Apple and Google Push Notifications for Laravel 5

## Installation

For Laravel 5.4 or older, add the provider in your `config/app.php` :

```php
    'providers' => [
        Bnb\PushNotifications\PushNotificationsServiceProvider::class,
    ],
```

Laravel 5.5 use _Composer_ auto-discovery feature.

## Configuration

### Configuration via environment 

To return the APNs payloads in the results array (debugging purpose) :

    PUSH_RETURN_PAYLOADS=true

#### Apple Push Notification Service

Put your APNs certificate somewhere inside your project path. Add the relative path to your certificate in your `.env` file :

    PUSH_APNS_CERTIFICATE=config/push/certificate.pem

If your certificate is secured with a password you can specify it in the `.env` file as :

    PUSH_APNS_PASSWORD=changeme
    
You can also set the environnement to use (default to `production`) :

    PUSH_APNS_ENVIRONMENT=development

#### Google Cloud Messaging

Grap you API key from the Google Developer Console and add it to your `.env` file :

    PUSH_GCM_KEY=AIaeRtYiUoP-QsDfghQJK1lMWXCvBN23AZE4RT6u
    
### Configuration via PHP
 
If you prefer to configure the module from a `config` PHP file, publish it :

    php artisan vendor:publish --provider=Bnb\\PushNotifications\\PushNotificationsServiceProvider --tag=config
    
Then set the desired configuration values :

```php
<?php

return [

    'apns' => [
        'environement' => 'production',
        'certificate' => __DIR__ . '/push/certificate.pem',
        'password'    => 'changeme',
    ],

    'gcm' => [
        'key' => 'AIaeRtYiUoP-QsDfghQJK1lMWXCvBN23AZE4RT6u',
    ],
    
    // the size of the chunk batch loop
    'chunk' => 100,
    
    // set to true to return the APNs payloads in the results array
    'payloads' => false,

];
```

### Configuration at runtime

The GCM and APNs configuration can be changed at runtime via the `setGcmOption($key, $value)` and `setApnsOption($key, $value)` methods of the `Notification` class.

#### GCM options

| Key | Default | Description |
|------|------|-------------|
| key | `config('push.gcm.key')` | The GCM server API key |

#### APNs options

| Key | Default | Description |
|------|------|-------------|
| certificate | `base_path(config('push.apns.certificate'))` | The APNs certificate path on the server filesystem |
| password | `config('push.apns.password')` | The APNs certificate password |
| environment | `config('push.apns.environment')` | The APNs environnement |


## Usage

A notification message holds the following properties where each one can be overridden by the device definition :

| Name | Type | Description |
|------|------|-------------|
| title | string | The title displayed in the notification bar |
| message | message | The message displayed in the notification bar (platform dependent) |
| badge | int | The badge number displayed in the notification bar |
| sound |  string | The sound to play when the notification is received (platform dependent) |
| ttl |  int | Number of seconds after which the message is expired by the network |
| metadata | array | Key/Value pairs of custom data |

```php
$notification = new Notification('title', 'message');
$notification
    ->badge(5)
    ->sound('sound')
    ->ttl(1234)
    ->metadata('key1', 'value1')
    ->metadata('key2', 'value2');
```

```php
$device = Device::apns('a-token', 'a-unique-local-id');
$device
    ->title('deviceTitle')
    ->message('deviceMessage')
    ->badge(10)
    ->sound('deviceSound')
    ->ttl(4321)
    ->metadata('key1', 'deviceValue1')
    ->metadata('deviceKey2', 'value2');
```

### Metadata

For APNs the `props` custom property holds the list of the metadata keys including `title` and `message`.

For GCM the `metadata` are bound to the `appdata` object.

### Example

```php
$notification = new \Bnb\PushNotifications\Notification('Hello World !', 'This is a test message');
$notification->metadata('custom-id', 1234);

$notification->push(\Bnb\PushNotifications\Device::gcm('test-token')->badge(3)->metadata('device-key','demoGcm'));
$notification->push(\Bnb\PushNotifications\Device::apns('test-token')->badge(2)->metadata('device-key','demoApns'));

$results = $notification->send();

// $results['errors'] // Contains the list of failed devices
// $results['updates'] // Contains the list of updated token devices (GCM)
// $results['payloads'] // Contains the messages payloads (APNs only) if config('push.payloads') is set to true

foreach($results['errors'] as $data) {
    DbDevice::where('token', $data->token)
        ->delete();
}

foreach($results['updates'] as $data) {
    DbDevice::where('token', $data['device']->token)
        ->update(['token' => $data['token']]);
}
```

### Commands

You can use the following Artisan command lines to send test messages :

#### Send to Android devices

`php artisan push:gcm [options] [--] <token> <message> [<title>]`

```
Arguments:
  token                        the device token
  message                      the notification message
  title                        (optional) the notification title

Options:
      --sender-id[=SENDER-ID]  The GCM sender ID
```

Example :

```
php artisan push:gcm "ebizwJXzS7o:APA91bEa6tnBa-ZTkSf0fnsGNvU1BLdMnSi09GQ6BkFp-p99wSyVqb0f1nZpE3UEb-w3TzlrwhRGG1YQC0SV9N4DwO17RdceUX77ahAYtWcpFMgC4Xnc3NSkQ9PSqYfeFRPDL6D_KORM" "This is a test message"
```

#### Send to iOS devices

`php artisan push:apns [options] [--] <token> <message> [<title>]`

```
Arguments:
  token                            the device token
  message                          the notification message
  title                            (optional) the notification title

Options:
      --certificate[=CERTIFICATE]  The Apple certificate path
      --password[=PASSWORD]        The Apple certificate password
      --environment[=ENVIRONMENT]  The Apple push environment (production or development)
```

Example :

```
php artisan push:apns "3c1c1c88428aeec68525a3e3d23c632bfef8c076c45e3af6769501b4ba493b1b" "This is a test message" "Hello World"
```