# itpalert/web2sms

A modern PHP client library for the Web2SMS API with type-safe response objects and comprehensive error handling.

Official API documentation: https://wiki.web2sms.ro/api-web2sms-rest-client/2-rest-api-overview

## Requirements

- PHP 8.0 or higher
- PSR-18 HTTP Client (Guzzle 6+ or 7+)

## Installation

Install via Composer:
```bash
composer require itpalert/web2sms
```

## Configuration

### Laravel Setup

Add the following environment variables to your `.env` file:
```env
WEB2SMS_KEY=8c78axxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
WEB2SMS_SECRET=e9a689cfxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
WEB2SMS_SMS_FROM=ALERT
WEB2SMS_ACCOUNT_TYPE=prepaid
```

Add the configuration to `config/services.php`:
```php
'web2sms' => [
    'key' => env('WEB2SMS_KEY'),
    'secret' => env('WEB2SMS_SECRET'),
    'sms_from' => env('WEB2SMS_SMS_FROM', ''),
    'account_type' => env('WEB2SMS_ACCOUNT_TYPE', 'prepaid'),
],
```

## Usage

### Using the Laravel Facade
```php
use ITPalert\Web2sms\SMS;
use ITPalert\Web2sms\Facades\Web2sms;

// Create SMS message
$sms = new SMS(
    to: '0712345678',        // Recipient phone number
    from: '',                // Sender (optional, uses config default)
    message: 'Hello World',  // Message content
    type: 'text'             // 'text' or 'unicode'
);

// Send SMS
$response = Web2sms::send($sms);

if ($response->isSuccess()) {
    $messageId = $response->getMessageId();
    echo "SMS sent successfully! Message ID: {$messageId}";
} else {
    echo "Error: {$response->getErrorMessage()}";
}
```

### Using the Client Directly
```php
use ITPalert\Web2sms\Credentials\Basic;
use ITPalert\Web2sms\Client;
use ITPalert\Web2sms\SMS;

// Initialize credentials
$credentials = new Basic(
    apiKey: 'your_api_key',
    apiSecret: 'your_api_secret',
    accountType: 'prepaid' // or 'postpaid'
);

// Create client
$client = new Client($credentials);

// Create and send SMS
$sms = new SMS(
    to: '0712345678',
    from: 'ALERT',
    message: 'Hello World',
    type: 'text'
);

$response = $client->send($sms);
```

## Working with SMS Messages

### Creating SMS Messages
```php
use ITPalert\Web2sms\SMS;

$sms = new SMS(
    to: '0712345678',        // Formats: +40712345678, 40712345678, 0712345678
    from: 'ALERT',           // Sender ID from Web2SMS platform
    message: 'Your message', // Message content
    type: 'text'             // 'text' or 'unicode'
);
```

**Message Types:**
- `text`: Automatically converts Unicode characters to GSM-7 compatible characters
- `unicode`: Preserves Unicode characters (uses more credits per message)

### Optional Message Settings
```php
// Set client reference (max 40 characters)
$sms->setClientRef('ORDER-12345');

// Set delivery receipt callback URL
$sms->setDeliveryReceiptCallback('https://yoursite.com/callback');

// Set displayed message (overrides actual message content in Web2SMS platform)
$sms->setDisplayedMessage('Visible message');

// Schedule message for future delivery
$sms->setSchedule('2024-12-31 10:30:00'); // Format: Y-m-d H:i:s
// Or use DateTime object
$sms->setSchedule(new DateTime('+2 hours'));
```

### Getting Message Segment Count
```php
$segments = $sms->getSegmentCount();
echo "This message will use {$segments} SMS credits";
```

## API Methods

### Send SMS
```php
$response = $client->send($sms);

if ($response->isSuccess()) {
    $messageId = $response->getMessageId();
    echo "Message ID: {$messageId}";
}
```

**Returns:** `SendResponse`

### Get Message Status
```php
$response = $client->get($messageId);

if ($response->isSuccess()) {
    $status = $response->getStatus();
    echo "Status: {$status}";
}
```

**Returns:** `StatusResponse`

**Status Codes:**
- `0`: Pending
- `1`: Sent to carrier
- `2`: Delivered
- `3`: Failed
- `4`: Expired

### Delete Scheduled Message
```php
$response = $client->delete($messageId);

if ($response->isDeleted()) {
    echo "Message deleted successfully";
}
```

**Returns:** `DeleteResponse`

### Check Account Balance
```php
$response = $client->balance();

if ($response->isSuccess()) {
    $balance = $response->getBalance();
    echo "Balance: {$balance}";
}
```

**Returns:** `BalanceResponse`

## Response Objects

All API methods return typed response objects with the following methods:
```php
// Check if request was successful
$response->isSuccess(); // Returns true if error code is 0

// Get error details
$response->getErrorCode();    // Returns int
$response->getErrorMessage(); // Returns string

// Get raw response data
$response->toArray(); // Returns array
```

### Method-Specific Response Methods

**SendResponse:**
```php
$response->getMessageId(); // Returns message hash ID
```

**StatusResponse:**
```php
$response->getStatus(); // Returns status code
```

**DeleteResponse:**
```php
$response->isDeleted(); // Returns true if result is 'DELETED'
```

**BalanceResponse:**
```php
$response->getBalance(); // Returns balance value
```

## Error Handling
```php
use ITPalert\Web2sms\Exceptions\Exception;

try {
    $response = $client->send($sms);
    
    if ($response->isSuccess()) {
        // Handle success
        $messageId = $response->getMessageId();
    } else {
        // Handle API-level errors (non-zero error code)
        Log::warning('Web2SMS API Error', [
            'code' => $response->getErrorCode(),
            'message' => $response->getErrorMessage(),
        ]);
    }
} catch (Exception $e) {
    // Handle HTTP/network errors
    Log::error('Web2SMS Request Failed', [
        'error' => $e->getMessage(),
    ]);
}
```

## Error Codes

Common Web2SMS API error codes:

| Code | Message | Description |
|------|---------|-------------|
| 0 | `IDS_Prepaid_MessageController_E_OK` | Success |
| 268435457 | No available account | Calling IP not authorized |
| 268435458 | Wrong phone format | Invalid phone number or network not configured |
| 268435459 | Empty message | Message content is empty |
| 268435460 | Monthly limit exceeded | SMS sending limit reached |
| 268435462 | Account misconfigured | Account configuration error |
| 268435463 | Account disabled | Account is disabled |
| 268435464 | Internal error | Error creating SMS sender |
| 268435465 | Internal error | Error scheduling SMS |
| 268435466 | Phone blacklisted | Number is on blacklist |
| 268435488 | Time restriction | Scheduled time outside allowed interval |
| 268435520 | Network not configured | GSM network not configured for account |
| 536870913 | Internal error | Internal Web2SMS error |

## Examples

### Complete Laravel Example
```php
use ITPalert\Web2sms\SMS;
use ITPalert\Web2sms\Facades\Web2sms;
use Illuminate\Support\Facades\Log;

public function sendNotification($phoneNumber, $message)
{
    $sms = new SMS(
        to: $phoneNumber,
        from: '',
        message: $message,
        type: 'text'
    );
    
    $sms->setClientRef('NOTIFICATION-' . now()->timestamp);
    
    try {
        $response = Web2sms::send($sms);
        
        if ($response->isSuccess()) {
            return [
                'success' => true,
                'message_id' => $response->getMessageId(),
                'segments' => $sms->getSegmentCount(),
            ];
        }
        
        Log::warning('SMS sending failed', [
            'error_code' => $response->getErrorCode(),
            'error_message' => $response->getErrorMessage(),
            'phone' => $phoneNumber,
        ]);
        
        return [
            'success' => false,
            'error' => $response->getErrorMessage(),
        ];
        
    } catch (\Exception $e) {
        Log::error('SMS exception', [
            'error' => $e->getMessage(),
            'phone' => $phoneNumber,
        ]);
        
        return [
            'success' => false,
            'error' => 'Network error occurred',
        ];
    }
}
```

### Scheduled SMS Example
```php
$sms = new SMS(
    to: '0712345678',
    from: 'ALERT',
    message: 'Appointment reminder: Tomorrow at 10:00 AM',
    type: 'text'
);

// Schedule for tomorrow at 9:00 AM
$sms->setSchedule((new DateTime('tomorrow'))->setTime(9, 0));

$response = Web2sms::send($sms);
```

### Bulk SMS Example
```php
$recipients = ['0712345678', '0723456789', '0734567890'];

foreach ($recipients as $recipient) {
    $sms = new SMS(
        to: $recipient,
        from: 'PROMO',
        message: 'Special offer: 50% off today only!',
        type: 'text'
    );
    
    $response = Web2sms::send($sms);
    
    if ($response->isSuccess()) {
        echo "Sent to {$recipient}: {$response->getMessageId()}\n";
    }
    
    // Rate limiting - avoid sending too fast
    usleep(100000); // 100ms delay
}
```

## License

MIT License

## Support

For issues or questions:
- GitHub: https://github.com/itpalert/web2sms/issues
- Email: contact@web2sms.ro

## Credits

Maintained by ITPalert