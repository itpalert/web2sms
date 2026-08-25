<?php

namespace ITPalert\Web2sms\Testing;

use Closure;
use ITPalert\Web2sms\Contracts\Client;
use ITPalert\Web2sms\Responses\BalanceResponse;
use ITPalert\Web2sms\Responses\DeleteResponse;
use ITPalert\Web2sms\Responses\SendResponse;
use ITPalert\Web2sms\Responses\StatusResponse;
use ITPalert\Web2sms\SMS;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Log\LoggerInterface;

/**
 * Records messages instead of delivering them.
 *
 * A text message is the one kind of outbound that costs money per attempt and
 * arrives on a real handset, so a suite that has not thought about SMS must
 * not be able to send one. This stands behind the facade and the notification
 * channel whenever the driver is not `api`, which under the testing
 * environment is the default.
 *
 * Messages still go through SMS::verifyMessage() on the way in, the same check
 * the real client runs before dialling out, so a test that builds a message
 * with no recipient or no body fails here rather than passing quietly. A fake
 * that accepts anything is a fake that hides bugs until production.
 *
 * Pass a logger to have it write what it swallowed, which is the useful mode
 * during local development: you read the rendered message without spending
 * credit or waiting for a phone.
 */
class Web2smsFake implements Client
{
    /** @var array<int, SMS> */
    protected array $sent = [];

    /** @var array<int, string> */
    protected array $fetched = [];

    /** @var array<int, string> */
    protected array $deleted = [];

    protected int $balanceCalls = 0;

    /** What balance() reports back. Set it when a test branches on credit. */
    protected float $balance = 0.0;

    public function __construct(protected ?LoggerInterface $logger = null)
    {
    }

    public function send(SMS $message): SendResponse
    {
        $message->verifyMessage();

        $this->sent[] = $message;

        $this->logger?->debug(sprintf(
            "Web2sms message not sent (fake driver).\nFrom: %s\nTo: %s\nBody:\n%s",
            $message->getFrom(),
            $message->getTo(),
            $message->getMessage(),
        ));

        return new SendResponse(
            data: ['id' => 'fake-'.count($this->sent)],
            errorCode: 0,
            errorMessage: '',
        );
    }

    public function get(string $id): StatusResponse
    {
        $this->fetched[] = $id;

        return new StatusResponse(data: ['id' => $id], errorCode: 0, errorMessage: '');
    }

    public function delete(string $id): DeleteResponse
    {
        $this->deleted[] = $id;

        return new DeleteResponse(data: ['id' => $id], errorCode: 0, errorMessage: '');
    }

    public function balance(): BalanceResponse
    {
        $this->balanceCalls++;

        return new BalanceResponse(
            data: ['balance' => $this->balance],
            errorCode: 0,
            errorMessage: '',
        );
    }

    public function shouldReportBalance(float $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Messages recorded so far, optionally filtered.
     *
     * @param  Closure(SMS): bool|null  $callback
     * @return array<int, SMS>
     */
    public function sent(?Closure $callback = null): array
    {
        if ($callback === null) {
            return $this->sent;
        }

        return array_values(array_filter($this->sent, $callback));
    }

    public function hasSent(): bool
    {
        return $this->sent !== [];
    }

    /**
     * @param  Closure(SMS): bool|null  $callback
     */
    public function assertSent(?Closure $callback = null): self
    {
        PHPUnit::assertNotEmpty(
            $this->sent($callback),
            $callback === null
                ? 'Expected a text message to be sent, but none was.'
                : 'Expected a text message matching the given condition, but none was sent.',
        );

        return $this;
    }

    /**
     * @param  Closure(SMS): bool|null  $callback
     */
    public function assertSentTo(string $to, ?Closure $callback = null): self
    {
        $matching = $this->sent(
            fn (SMS $sms): bool => $sms->getTo() === $to && ($callback === null || $callback($sms))
        );

        PHPUnit::assertNotEmpty($matching, "Expected a text message to [{$to}], but none was sent there.");

        return $this;
    }

    /**
     * @param  Closure(SMS): bool|null  $callback
     */
    public function assertNotSent(?Closure $callback = null): self
    {
        PHPUnit::assertEmpty(
            $this->sent($callback),
            'Expected no matching text message, but one was sent.',
        );

        return $this;
    }

    public function assertNothingSent(): self
    {
        $recipients = implode(', ', array_map(fn (SMS $sms): string => $sms->getTo(), $this->sent));

        PHPUnit::assertEmpty(
            $this->sent,
            "Expected no text messages, but messages were sent to: {$recipients}.",
        );

        return $this;
    }

    public function assertSentCount(int $count): self
    {
        PHPUnit::assertCount(
            $count,
            $this->sent,
            "Expected {$count} text messages, got ".count($this->sent).'.',
        );

        return $this;
    }

    /** @return array<int, string> */
    public function fetched(): array
    {
        return $this->fetched;
    }

    /** @return array<int, string> */
    public function deleted(): array
    {
        return $this->deleted;
    }

    public function balanceCallCount(): int
    {
        return $this->balanceCalls;
    }

    public function reset(): self
    {
        $this->sent = [];
        $this->fetched = [];
        $this->deleted = [];
        $this->balanceCalls = 0;

        return $this;
    }
}
