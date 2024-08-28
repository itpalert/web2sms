# itpalert/web2sms

Official documentation: https://wiki.web2sms.ro/api-web2sms-rest-client/2-rest-api-overview

Install it via:
```
composer require itpalert/web2sms
```

### Setting up the web2sms service for using with the facade

Add the following env variables to your `.env`:

```php
// .env
...
WEB2SMS_KEY=8c78axxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
WEB2SMS_SECRET=e9a689cfxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
WEB2SMS_ACCOUNT_TYPE=prepaid
...
```

Add the folowing to the `config/services.php`

```php
    'web2sms' => [
        'key' => env('WEB2SMS_KEY'),
        'secret' => env('WEB2SMS_SECRET'),
        'account_type' => env('WEB2SMS_ACCOUNT_TYPE', 'prepaid'),
    ],
```

Use it with this:

```php
use ITPalert\Web2sms\SMS;
use ITPalert\Web2sms\Facades\Web2sms;

$web2smsSms = new SMS(
  $to,      //message recipient +40712345678, 40712345678, 0712345678
  $from,    //message sender number from web2sms platform
  $content, //message text
  $type     //text or unicode
);

Web2sms::send($web2smsSms);
```

### Setting up the web2sms service for using it with the client

```
use ITPalert\Web2sms\Credentials\Basic;
use ITPalert\Web2sms\Client;

$credentials = new Basic('api_key', 'api_secret'); //credentials from web2sms platform

$client = new Client(
  CredentialsInterface $credentials,
  array $options = [], // options to client(currently unused)
  ?ClientInterface $client = null //Guzzle client(you can leave it null)
); 
```
After initializing the Web2sms client you need to create an SMS instance.
```
use ITPalert\Web2sms\SMS;

$web2smsSms = new SMS(
  $to,      //message recipient +40712345678, 40712345678, 0712345678
  $from,    //message sender number from web2sms platform
  $content, //message text
  $type     //text or unicode
);
```

If you initialize the SMS instance to type 'text' the program will automatically convert all unicode characters to GSM-7 compatible characters.

Available fluent setters available on the SMS instance:

```
$web2smsSms->setType('unicode');

$web2smsSms->setClientRef(clientReference);

$web2smsSms->setDeliveryReceiptCallback(CallbackURL);

$web2smsSms->setDisplayedMessage('message shown in place of actual sms text');

$web2smsSms->setSchedule('YYYY-MM-DD 10:20:10');
```

Finally you need to pass the SMS instance to the web2sms client.
```
$client->send($web2smsSms); // returns the hashID of the the message
```

I have implemented all the methods available on the web2sms API.
```
$client->get(string $id); //get the status of the message

$client->delete(string $id); //delete message

$client->balance();
```

