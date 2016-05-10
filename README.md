# Apple and Google Push Notifications for Laravel 5

## Installation

Add the provider in your `config/app.php` :

```php
    'providers' => [
        Bnb\PushNotifications\PushServiceProvider::class,
    ],
```

## Configuration

### Configuration via environement 

#### Apple Push Notification Service

Put your APNs certificate somewhere inside your project path. Add the relative path to your certificate in your `.env` file :

    PUSH_APNS_CERTIFICATE=config/push/certificate.pem

If your certificate is secured with a password you can specify it in the `.env` file as :

    PUSH_APNS_PASSWORD=changeme
    
You can also set the environnement to use (default to `production`) :

    PUSH_APNS_ENVIRONMENT=sandbox

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

];
```
    
## Usage

A notification message holds the following properties where each one can be overridden by the device definition :

| Name | Type | Description |
|------|------|-------------|
| title | string | The title displayed in the notification bar |
| message | message | The message displayed in the notification bar, if available |
| badge | int | The badge number displayed in the notification bar |
| sound |  string | The sound to play when the notification is received |
| ttl |  int | Number of seconds after which the message is expired in the network |
| metadata | array | Key/Pair values of custom data |

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
$notification = \Bnb\PushNotifications\Notification('Hello World !', 'This is a test message');
$notification->metadata('custom-id', 1234);

$notification->push(\Bnb\PushNotifications\Device::gcm('test-token')->badge(3)->metadata('device-key','demoGcm'));
$notification->push(\Bnb\PushNotifications\Device::apns('test-token')->badge(2)->metadata('device-key','demoApns'));

$results = $notification->send();

// $results['errors'] // Contains the list of failed devices
// $results['updates'] // Contains the list of updated token devices (GCM)

foreach($results['errors'] as $device) {
    DbDevice::where('token', $device->token)
        ->delete();
}

foreach($results['updates'] as $device) {
    DbDevice::where('token', $data['device']->token)
        ->update(['token' => $data['token']]);
}
```