<?php

namespace App\Jobs;

use App\Services\Pushwoosh\Message;
use App\Services\Pushwoosh\PushNotificationLog;
use App\Services\Pushwoosh\PushwooshApiServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Message
     */
    public $message;
    /**
     * @var PushNotificationLog
     */
    public $log;
    /**
     * @var string
     */
    public $applicationCode;

    /**
     * Create a new job instance.
     *
     * @param  Message  $message
     * @param  PushNotificationLog  $log
     * @param  string  $applicationCode
     */
    public function __construct(Message $message, PushNotificationLog $log, string $applicationCode)
    {
        $this->message = $message;
        $this->log = $log;
        $this->applicationCode = $applicationCode;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->log->canceled_at) {
            return;
        }

        $pushwoosh = resolve(PushwooshApiServiceInterface::class);
        $pushwoosh->setApplication($this->applicationCode);

        $messageIds = $pushwoosh->createMessage($this->message);

        if (!is_array($messageIds) || !array_key_exists(0, $messageIds)) {
            return;
        }
        $this->log->update([
            'sent_at' => now(),
            'pw_message_id' => $messageIds[0]
        ]);
    }
}
