<?php

namespace ITPalert\Web2sms\Responses;

class Response
{
    public function __construct(
        public readonly array $data,
        public readonly int $errorCode,
        public readonly string $errorMessage
    ) {}

    public function isSuccess(): bool
    {
        return $this->errorCode === 0;
    }

    public function result(): mixed
    {
        return null;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}