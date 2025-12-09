<?php

namespace ITPalert\Web2sms\Responses;

class DeleteResponse extends Response
{
    public function result(): ?string
    {
        return $this->data['result'] ?? null;
    }

    public function isDeleted(): bool
    {
        return $this->isSuccess() && $this->result() === 'DELETED';
    }
}