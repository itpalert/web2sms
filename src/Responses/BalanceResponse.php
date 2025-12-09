<?php

namespace ITPalert\Web2sms\Responses;

class BalanceResponse extends Response
{
    public function result(): ?string
    {
        // Balance value is in the error message when result is BALANCE
        return $this->isSuccess() && $this->data['result'] === 'BALANCE' 
            ? $this->errorMessage 
            : null;
    }

    public function getBalance(): ?string
    {
        return $this->result();
    }
}