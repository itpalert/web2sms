<?php

namespace ITPalert\Web2sms\Responses;

class StatusResponse extends Response
{
    public function result(): ?string
    {
        return $this->data['status'] ?? null;
    }

    public function getStatus(): ?string
    {
        return $this->result();
    }
}