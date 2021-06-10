<?php

namespace App\Services\Pushwoosh;

interface PushwooshApiServiceInterface
{
    /**
     * @param  Message  $message
     * @return mixed
     */
    public function createMessage(Message $message);
    
    /**
     * @param  string  $applicationCode
     * @return mixed
     */
    public function setApplication(string $applicationCode);
}
