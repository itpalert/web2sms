<?php

namespace ITPalert\Web2sms\Exceptions;

class Exception extends \Exception
{
    public $entity;

    /**
     * @param $entity
     */
    public function setEntity($entity): void
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}