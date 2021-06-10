<?php

namespace App\Resources\ClientManager;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

/**
 * @property int id
 * @property int base_config_id
 * @property string title
 * @property string content
 * @property bool use_recipient_timezone
 * @property bool shorten_url
 * @property string url
 * @property string canceled_at
 * @property string sent_at
 * @property string pending_at
 * @property string deliver_at
 */
class PushNotificationLogResource extends Resource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'base_config_id' => $this->base_config_id,
            'title' => $this->title,
            'content' => $this->content,
            'use_recipient_timezone' => $this->use_recipient_timezone,
            'shorten_url' => $this->shorten_url,
            'url' => $this->url,
            'deliver_at' => $this->deliver_at,
            'pending_at' => $this->pending_at,
            'sent_at' => $this->sent_at,
            'canceled_at' => $this->canceled_at,
            '_rowVariant' => $this->rowVariant(),
        ];
    }

    protected function rowVariant()
    {
        if ($this->sent_at) {
            return 'success';
        }

        if ($this->canceled_at) {
            return 'secondary';
        }
    }
}
