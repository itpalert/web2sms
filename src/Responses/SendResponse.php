<?php

namespace ITPalert\Web2sms\Responses;

class SendResponse extends Response
{
    public function result(): ?string
    {
        return $this->data['id'] ?? null;
    }

    public function getMessageId(): ?string
    {
        return $this->result();
    }
}