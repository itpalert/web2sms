# itpalert/web2sms

A modern PHP client library for the Web2SMS API with type-safe response objects, comprehensive error handling, and optional Laravel integration.

[![Tests](https://img.shields.io/badge/tests-passing-brightgreen)]()
[![PHP Version](https://img.shields.io/badge/php-%5E8.0-blue)]()
[![License](https://img.shields.io/badge/license-MIT-green)]()

Official API documentation: https://wiki.web2sms.ro/api-web2sms-rest-client/2-rest-api-overview

## Features

- ✅ **Type-safe response objects** - No more array guessing
- ✅ **Comprehensive error handling** - Clear exceptions with context
- ✅ **Automatic character conversion** - GSM-7 encoding with transliteration
- ✅ **Dynamic message type conversion** - Switch between text/unicode freely
- ✅ **Segment counting** - Know exactly how many SMS credits you'll use
- ✅ **Secure nonce generation** - Fresh nonce for every request
- ✅ **Reusable SMS objects** - Send the same message multiple times safely
- ✅ **Laravel integration** - Facade and Service Provider included
- ✅ **Framework agnostic** - Works with any PHP project
- ✅ **Fully tested** - Unit and integration tests included

## Requirements

- PHP 8.0 or higher
- Guzzle HTTP Client 7.2+

## Installation

Install via Composer:
```bash
composer require itpalert/web2sms
```

That's it! The package works out of the box.

## Quick Start

### Laravel

**1. Add configuration to `.env`:**
```env
WEB2SMS_KEY=your_api_key_here
WEB2SMS_SECRET=your_api_secret_here
WEB2SMS_SMS_FROM=YourSender
WEB2SMS_ACCOUNT_TYPE=prepaid
```

**2. Add to `config/services.php`:**
```php
'web2sms' => [
    'key' => env('WEB2SMS_KEY'),
    'secret' => env('WEB2SMS_SECRET'),
    'sms_from' => env('WEB2SMS_SMS_FROM', ''),
    'account_type' => env('WEB2SMS_ACCOUNT_TYPE', 'prepaid'),
],
```

**3. Send SMS using Facade:**
```php
use ITPalert\Web2sms\SMS;
use ITPalert\Web2sms\Facades\Web2sms;

$sms = new SMS(
    to: '0712345678',
    from: '',  // Uses config default
    message: 'Hello from Laravel!',
    type: 'text'
);

$response = Web2sms::send($sms);

if ($response->isSuccess()) {
    $messageId = $response->getMessageId();
    echo "SMS sent! Message ID: {$messageId}";
}
```

### Plain PHP / Other Frameworks
```php
use ITPalert\Web2sms\Credentials\Basic;
use ITPalert\Web2sms\Client;
use ITPalert\Web2sms\SMS;

// Create credentials
$credentials = new Basic(
    apiKey: 'your_api_key',
    apiSecret: 'your_api_secret',
    accountType: 'prepaid'  // or 'postpaid'
);

// Create client
$client = new Client($credentials, [
    'sms_from' => 'YourSender'  // Optional default sender
]);

// Create SMS
$sms = new SMS(
    to: '0712345678',
    from: 'YourSender',
    message: 'Hello World!',
    type: 'text'
);

// Send SMS
$response = $client->send($sms);

if ($response->isSuccess()) {
    echo "Message sent! ID: " . $response->getMessageId();
} else {
    echo "Error: " . $response->getErrorMessage();
}
```

## Usage Guide

### Creating SMS Messages
```php
use ITPalert\Web2sms\SMS;

$sms = new SMS(
    to: '0712345678',        // Formats: +40712345678, 40712345678, 0712345678
    from: 'SENDER',          // Sender ID from Web2SMS platform
    message: 'Your message', // Message content
    type: 'text'             // 'text' or 'unicode'
);
```

**Message Types:**

- **`text`** - Automatically converts Unicode characters to GSM-7 compatible characters (Romanian: ăîâșț → aiast)
- **`unicode`** - Preserves all Unicode characters (costs more credits per message)

### Message Type Flexibility

The SMS class stores your original message and converts it on-demand based on the current type:
```php
use ITPalert\Web2sms\SMS;

// Create with text type
$sms = new SMS(
    to: '0712345678',
    from: 'SENDER',
    message: 'Mesaj în română',
    type: 'text'
);

// Get converted message (GSM-7 compatible)
echo $sms->getMessage();
// Output: "Mesaj in romana"

// Get original message (unconverted)
echo $sms->getOriginalMessage();
// Output: "Mesaj în română"

// Change to unicode type
$sms->setType('unicode');

// Now getMessage() returns original without conversion
echo $sms->getMessage();
// Output: "Mesaj în română"

// Change back to text
$sms->setType('text');
echo $sms->getMessage();
// Output: "Mesaj in romana" (converted again)
```

**Key Points:**
- Original message is always preserved via `getOriginalMessage()`
- `getMessage()` returns the converted message based on current type
- Changing type with `setType()` automatically affects conversion
- Type conversion happens on-demand (not stored)

### Optional Message Settings
```php
// Set client reference (max 40 characters) - useful for tracking
$sms->setClientRef('ORDER-12345');

// Set delivery receipt callback URL
$sms->setDeliveryReceiptCallback('https://yoursite.com/sms-callback');

// Set displayed message (overrides actual message in Web2SMS platform UI)
$sms->setDisplayedMessage('Generic notification');

// Schedule message for future delivery
$sms->setSchedule('2024-12-31 10:30:00');
// Or use DateTime object
$sms->setSchedule(new DateTime('+2 hours'));
```

### Getting Message Segment Count

SMS messages are split into segments. Know how many credits you'll use:
```php
$sms = new SMS('0712345678', 'SENDER', 'Your message', 'text');
$segments = $sms->getSegmentCount();

echo "This message will use {$segments} SMS credit(s)";
```

**Segment Limits:**
- **GSM-7 (text)**: 160 chars = 1 segment, 161+ chars = multiple segments of 153 chars each
- **Unicode**: 70 chars = 1 segment, 71+ chars = multiple segments of 67 chars each
- **Extended GSM-7 chars** (`{}[]^~|€`): Count as 2 characters

## API Methods

### Send SMS
```php
$response = $client->send($sms);

if ($response->isSuccess()) {
    $messageId = $response->getMessageId();  // Store this for tracking
}
```

**Returns:** `SendResponse`

**Available Methods:**
- `isSuccess()` - Returns true if error code is 0
- `getMessageId()` - Returns the unique message ID (hash)
- `getErrorCode()` - Returns integer error code
- `getErrorMessage()` - Returns error message string
- `toArray()` - Returns raw response data

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
- `0` - Pending
- `1` - Sent to carrier
- `2` - Delivered
- `3` - Failed
- `4` - Expired

**Available Methods:**
- `getStatus()` - Returns status code as string
- Plus all base response methods

### Delete Scheduled Message
```php
$response = $client->delete($messageId);

if ($response->isDeleted()) {
    echo "Message deleted successfully";
}
```

**Returns:** `DeleteResponse`

**Available Methods:**
- `isDeleted()` - Returns true if successfully deleted
- Plus all base response methods

### Check Account Balance
```php
$response = $client->balance();

if ($response->isSuccess()) {
    $balance = $response->getBalance();
    echo "Balance: {$balance}";
}
```

**Returns:** `BalanceResponse`

**Available Methods:**
- `getBalance()` - Returns balance as string
- Plus all base response methods

## Error Handling

### API-Level Errors (Non-Zero Error Code)

These are returned in the response object, not thrown as exceptions:
```php
$response = $client->send($sms);

if (!$response->isSuccess()) {
    $errorCode = $response->getErrorCode();
    $errorMessage = $response->getErrorMessage();
    
    Log::warning('SMS failed', [
        'code' => $errorCode,
        'message' => $errorMessage,
    ]);
}
```

### HTTP/Network Errors

These are thrown as exceptions:
```php
use ITPalert\Web2sms\Exceptions\Exception;

try {
    $response = $client->send($sms);
    
    if ($response->isSuccess()) {
        // Success handling
    } else {
        // API error handling
    }
} catch (Exception $e) {
    // Network/HTTP error handling
    Log::error('SMS request failed', [
        'error' => $e->getMessage(),
    ]);
}
```

### Complete Error Handling Example
```php
use ITPalert\Web2sms\Exceptions\Exception;

try {
    $response = $client->send($sms);
    
    if ($response->isSuccess()) {
        // Deduct credits
        $user->smsCredits -= $sms->getSegmentCount();
        $user->save();
        
        // Log success
        Log::info('SMS sent', ['id' => $response->getMessageId()]);
        
        return ['success' => true, 'id' => $response->getMessageId()];
    }
    
    // API returned an error
    Log::warning('SMS API error', [
        'code' => $response->getErrorCode(),
        'message' => $response->getErrorMessage(),
    ]);
    
    return ['success' => false, 'error' => $response->getErrorMessage()];
    
} catch (Exception $e) {
    // Network/HTTP error
    Log::error('SMS network error', ['error' => $e->getMessage()]);
    
    return ['success' => false, 'error' => 'Network error occurred'];
}
```

## Error Codes Reference

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

## Advanced Usage

### Reusing SMS Objects

SMS objects can be safely reused for multiple sends. The Client automatically generates a fresh nonce for each request:
```php
$sms = new SMS('0712345678', 'ALERT', 'Notification', 'text');

// Send to the same recipient multiple times
$response1 = $client->send($sms); // Fresh nonce: 1734567890
sleep(1);
$response2 = $client->send($sms); // Fresh nonce: 1734567891
sleep(1);
$response3 = $client->send($sms); // Fresh nonce: 1734567892

// Each send is independent and secure
```

This is particularly useful for:
- Retry logic on failures
- Sending the same message at different times
- Testing without recreating SMS objects

### Custom HTTP Client

You can provide your own configured Guzzle client:
```php
use GuzzleHttp\Client as GuzzleClient;
use ITPalert\Web2sms\Client;

$httpClient = new GuzzleClient([
    'timeout' => 30,
    'verify' => true,
    // ... other Guzzle options
]);

$client = new Client($credentials, [], $httpClient);
```

### Laravel: Dependency Injection
```php
use ITPalert\Web2sms\Web2sms;

class NotificationService
{
    public function __construct(
        private Web2sms $web2sms
    ) {}
    
    public function sendSMS($to, $message)
    {
        $sms = new SMS($to, '', $message, 'text');
        return $this->web2sms->send($sms);
    }
}
```

### Bulk SMS Sending
```php
$recipients = ['0712345678', '0723456789', '0734567890'];
$message = 'Bulk notification message';

foreach ($recipients as $recipient) {
    $sms = new SMS($recipient, 'SENDER', $message, 'text');
    
    try {
        $response = $client->send($sms);
        
        if ($response->isSuccess()) {
            echo "Sent to {$recipient}: {$response->getMessageId()}\n";
        }
    } catch (Exception $e) {
        echo "Failed to send to {$recipient}: {$e->getMessage()}\n";
    }
    
    // Rate limiting - avoid sending too fast
    usleep(100000); // 100ms delay between messages
}
```

### Character Encoding Examples
```php
// Romanian text - automatically converted with type 'text'
$sms = new SMS('0712345678', 'ALERT', 'Bună ziua! Ați primit un mesaj.', 'text');
echo $sms->getMessage();
// Output: "Buna ziua! Ati primit un mesaj." (GSM-7 compatible)

// Preserve Romanian characters with type 'unicode' (costs more)
$sms = new SMS('0712345678', 'ALERT', 'Bună ziua! Ați primit un mesaj.', 'unicode');
echo $sms->getMessage();
// Output: "Bună ziua! Ați primit un mesaj." (original preserved)

// Check segments before sending
echo $sms->getSegmentCount(); // Know the cost

// Access original message regardless of type
echo $sms->getOriginalMessage(); // Always returns: "Bună ziua! Ați primit un mesaj."
```

**Supported Transliterations:**
- Romanian: `ăîâșț` → `aiast`, `ĂÎÂȘȚ` → `AIAST`
- Polish: `ąćęłńóśźż` → `acelnoszz`
- Czech/Slovak: `čďěňřšťůž` → `cdenrstuz`
- French: `œ` → `oe`, `Œ` → `OE`
- And many more...

## Examples

### Laravel Notification
```php
use ITPalert\Web2sms\SMS;
use ITPalert\Web2sms\Facades\Web2sms;

class OrderConfirmed
{
    public function send($order)
    {
        $message = "Order #{$order->id} confirmed! Total: {$order->total} RON";
        
        $sms = new SMS(
            to: $order->customer->phone,
            from: '',
            message: $message,
            type: 'text'
        );
        
        $sms->setClientRef("ORDER-{$order->id}");
        
        $response = Web2sms::send($sms);
        
        if ($response->isSuccess()) {
            $order->update([
                'sms_sent' => true,
                'sms_message_id' => $response->getMessageId(),
            ]);
        }
    }
}
```

### Scheduled Reminder
```php
$sms = new SMS(
    to: '0712345678',
    from: 'REMINDER',
    message: 'Appointment tomorrow at 10:00 AM. Reply CONFIRM to confirm.',
    type: 'text'
);

// Schedule for tomorrow at 9:00 AM
$sms->setSchedule((new DateTime('tomorrow'))->setTime(9, 0));

$response = $client->send($sms);

if ($response->isSuccess()) {
    echo "Reminder scheduled: " . $response->getMessageId();
}
```

### Two-Factor Authentication
```php
$code = random_int(100000, 999999);

$sms = new SMS(
    to: $user->phone,
    from: 'VERIFY',
    message: "Your verification code is: {$code}",
    type: 'text'
);

$response = $client->send($sms);

if ($response->isSuccess()) {
    // Store code in session/cache
    session(['2fa_code' => $code, '2fa_expires' => now()->addMinutes(5)]);
}
```

### Dynamic Type Switching
```php
// Start with text type for cost efficiency
$sms = new SMS(
    to: '0712345678',
    from: 'ALERT',
    message: 'Mesaj important în română',
    type: 'text'
);

// Check if conversion is acceptable
echo $sms->getMessage(); // "Mesaj important in romana"

// If diacritics are important, switch to unicode
if ($requiresDiacritics) {
    $sms->setType('unicode');
    echo $sms->getMessage(); // "Mesaj important în română"
}

// Send with the chosen type
$response = $client->send($sms);
```

## Testing

Run the test suite:
```bash
composer test
```

Run specific test suites:
```bash
# Unit tests only
vendor/bin/phpunit --testsuite "Unit Tests"

# Laravel integration tests only
vendor/bin/phpunit --testsuite "Laravel Integration"
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Changelog

### v2.2.0 (2024-12-12)

**Breaking Changes:**
- Removed `getNonce()` method from SMS class (nonce now generated by Client)

**New Features:**
- Added `getOriginalMessage()` to SMS class
- Dynamic message type conversion (change type and conversion updates automatically)
- Improved SMS object reusability (can safely send multiple times)

**Improvements:**
- Fresh nonce generated for each request (better security)
- Cleaner separation of concerns (SMS = data, Client = requests)

### v2.1.1 (2024-12-11)

**New Features:**
- Added comprehensive Laravel integration tests
- Framework-agnostic architecture with optional Laravel support

### v2.0.1 (2024-12-10)

**Bug Fixes:**
- Fixed Unicode to GSM-7 conversion bug in `SMS::setMessage()`

**Improvements:**
- Added comprehensive test suite (40+ tests)
- Simplified Guzzle dependency management

### v2.0.0 (2024-12-09)

Major rewrite with breaking changes:
- Added typed response objects (`SendResponse`, `StatusResponse`, etc.)
- Improved error handling with dedicated exception classes
- Simplified API with cleaner method signatures
- Requires PHP 8.0+

See [CHANGELOG.md](CHANGELOG.md) for full release notes.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Support

For issues, questions, or contributions:

- **GitHub Issues**: https://github.com/itpalert/web2sms/issues
- **Email**: gereattila98@gmail.com
- **Web2SMS Support**: contact@web2sms.ro

## Credits

- **Author**: Gere Attila
- **Web2SMS API**: [Web2SMS Romania](https://www.web2sms.ro)