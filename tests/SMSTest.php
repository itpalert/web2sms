<?php

namespace ITPalert\Web2sms\Tests;

use PHPUnit\Framework\TestCase;
use ITPalert\Web2sms\SMS;
use DateTime;
use InvalidArgumentException;
use RuntimeException;

class SMSTest extends TestCase
{
    public function test_sms_can_be_created_with_required_parameters()
    {
        $sms = new SMS('0712345678', 'ALERT', 'Test message', 'text');

        $this->assertEquals('0712345678', $sms->getTo());
        $this->assertEquals('ALERT', $sms->getFrom());
        $this->assertEquals('Test message', $sms->getMessage());
        $this->assertEquals('text', $sms->getType());
    }

    public function test_sms_converts_unicode_to_gsm7_for_text_type()
    {
        $originalMessage = 'Test ăîâșț message';
        $sms = new SMS('0712345678', 'ALERT', $originalMessage, 'text');

        $convertedMessage = $sms->getMessage();
        
        // Verify Romanian diacritics were converted to their ASCII equivalents
        $this->assertEquals('Test aiast message', $convertedMessage);
        
        // Verify conversion actually happened
        $this->assertNotEquals($originalMessage, $convertedMessage);
    }

    public function test_sms_preserves_unicode_for_unicode_type()
    {
        $sms = new SMS('0712345678', 'ALERT', 'Test ăîâșț message', 'unicode');

        $this->assertEquals('Test ăîâșț message', $sms->getMessage());
    }

    public function test_set_client_ref_accepts_valid_length()
    {
        $sms = new SMS('0712345678', 'ALERT', 'Test', 'text');
        $sms->setClientRef('ORDER-12345');

        $this->assertEquals('ORDER-12345', $sms->getClientRef());
    }

    public function test_set_client_ref_throws_exception_for_long_string()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Client Ref can be no more than 40 characters');

        $sms = new SMS('0712345678', 'ALERT', 'Test', 'text');
        $sms->setClientRef(str_repeat('a', 41));
    }

    public function test_set_schedule_with_string()
    {
        $sms = new SMS('0712345678', 'ALERT', 'Test', 'text');
        $sms->setSchedule('2024-12-31 10:30:00');

        $this->assertEquals('2024-12-31 10:30:00', $sms->getSchedule());
    }

    public function test_set_schedule_with_datetime()
    {
        $sms = new SMS('0712345678', 'ALERT', 'Test', 'text');
        $date = new DateTime('2024-12-31 10:30:00');
        $sms->setSchedule($date);

        $this->assertEquals('2024-12-31 10:30:00', $sms->getSchedule());
    }

    public function test_set_delivery_receipt_callback()
    {
        $sms = new SMS('0712345678', 'ALERT', 'Test', 'text');
        $sms->setDeliveryReceiptCallback('https://example.com/callback');

        $this->assertEquals('https://example.com/callback', $sms->getDeliveryReceiptCallback());
    }

    public function test_set_displayed_message()
    {
        $sms = new SMS('0712345678', 'ALERT', 'Secret message', 'text');
        $sms->setDisplayedMessage('Public message');

        $this->assertEquals('Public message', $sms->getDisplayedMessage());
    }

    public function test_get_segment_count_for_short_gsm7_message()
    {
        $sms = new SMS('0712345678', 'ALERT', 'Short message', 'text');

        $this->assertEquals(1, $sms->getSegmentCount());
    }

    public function test_get_segment_count_for_exactly_160_chars()
    {
        // Exactly 160 characters = 1 segment
        $message = str_repeat('a', 160);
        $sms = new SMS('0712345678', 'ALERT', $message, 'text');

        $this->assertEquals(1, $sms->getSegmentCount());
    }

    public function test_get_segment_count_for_long_gsm7_message()
    {
        // 161 characters: ceil(161 / 153) = 2 segments
        $message = str_repeat('a', 161);
        $sms = new SMS('0712345678', 'ALERT', $message, 'text');

        $this->assertEquals(2, $sms->getSegmentCount());
    }

    public function test_get_segment_count_for_exactly_306_chars()
    {
        // Exactly 306 characters: ceil(306 / 153) = 2 segments
        $message = str_repeat('a', 306);
        $sms = new SMS('0712345678', 'ALERT', $message, 'text');

        $this->assertEquals(2, $sms->getSegmentCount());
    }

    public function test_get_segment_count_for_exactly_70_unicode_chars()
    {
        // Exactly 70 Unicode characters = 1 segment
        $message = str_repeat('ă', 70);
        $sms = new SMS('0712345678', 'ALERT', $message, 'unicode');

        $this->assertEquals(1, $sms->getSegmentCount());
    }

    public function test_get_segment_count_for_unicode_message()
    {
        // 71 Unicode characters: ceil(71 / 67) = 2 segments
        $message = str_repeat('ă', 71);
        $sms = new SMS('0712345678', 'ALERT', $message, 'unicode');

        $this->assertEquals(2, $sms->getSegmentCount());
    }

    public function test_verify_message_throws_exception_for_empty_recipient()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('INVALID_RECIVER');

        $sms = new SMS('', 'ALERT', 'Test', 'text');
        $sms->verifyMessage();
    }

    public function test_verify_message_throws_exception_for_empty_message()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('INVALID_MESSAGE');

        $sms = new SMS('0712345678', 'ALERT', '', 'text');
        $sms->verifyMessage();
    }

    public function test_to_array_includes_required_fields()
    {
        $sms = new SMS('0712345678', 'ALERT', 'Test message', 'text');
        $array = $sms->toArray();

        $this->assertArrayHasKey('sender', $array);
        $this->assertArrayHasKey('recipient', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('nonce', $array);
        $this->assertEquals('ALERT', $array['sender']);
        $this->assertEquals('0712345678', $array['recipient']);
        $this->assertEquals('Test message', $array['message']);
    }

    public function test_to_array_includes_optional_fields_when_set()
    {
        $sms = new SMS('0712345678', 'ALERT', 'Test', 'text');
        $sms->setClientRef('REF-123');
        $sms->setSchedule('2024-12-31 10:30:00');
        $sms->setDeliveryReceiptCallback('https://example.com/callback');
        $sms->setDisplayedMessage('Public');

        $array = $sms->toArray();

        $this->assertArrayHasKey('userData', $array);
        $this->assertArrayHasKey('scheduleDatetime', $array);
        $this->assertArrayHasKey('callbackUrl', $array);
        $this->assertArrayHasKey('visibleMessage', $array);
        $this->assertEquals('REF-123', $array['userData']);
        $this->assertEquals('2024-12-31 10:30:00', $array['scheduleDatetime']);
        $this->assertEquals('https://example.com/callback', $array['callbackUrl']);
        $this->assertEquals('Public', $array['visibleMessage']);
    }

    public function test_nonce_is_generated_automatically()
    {
        $sms = new SMS('0712345678', 'ALERT', 'Test', 'text');
        $nonce = $sms->getNonce();

        $this->assertIsString($nonce);
        $this->assertGreaterThan(0, (int)$nonce);
    }

    public function test_nonce_is_consistent()
    {
        $sms = new SMS('0712345678', 'ALERT', 'Test', 'text');
        $nonce1 = $sms->getNonce();
        $nonce2 = $sms->getNonce();

        $this->assertEquals($nonce1, $nonce2);
    }
}