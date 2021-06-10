<?php

namespace App\Http\Controllers\Pushwoosh;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendPushNotificationRequest;
use App\Jobs\SendPushNotification;
use App\Resources\ClientManager\PushNotificationLogResource;
use App\Services\Pushwoosh\Message;
use App\Services\Pushwoosh\PushNotificationLog;
use App\Services\Pushwoosh\PushNotificationLogRepositoryInterface;
use Illuminate\Support\Carbon;
use SpOTTCommon\Client\BaseConfig;
use SpOTTCommon\Content\DistributionType;
use SpOTTCommon\Core\PaymentSetting;

class PushNotificationController extends Controller
{

    /**
     * @var PushNotificationLogRepositoryInterface
     */
    protected $pushNotificationLog;

    public function __construct(PushNotificationLogRepositoryInterface $pushNotificationLogRepository)
    {
        $this->pushNotificationLog = $pushNotificationLogRepository;
    }

    public function index(int $id)
    {
        return PushNotificationLogResource::collection(
            PushNotificationLog::query()
                ->where('base_config_id', $id)
                ->orderBy('pending_at', 'DESC')
                ->paginate(25)
        );
    }

    public function send(SendPushNotificationRequest $request)
    {
        $baseConfig = BaseConfig::findOrFail((int) $request->header('X-BaseConfigId'));

        abort_if(
            (
                !optional($baseConfig->pushwooshSetting)->active &&
                !optional($baseConfig->pushwooshSetting)->active_on_mobile
            )
            || !$baseConfig->pushwooshSetting->application_code
            || !$baseConfig->pushwooshSetting->channels_tag,
            403,
            'You don`t have Push Messages configured correctly!'
        );

        $deliverAt = Carbon::createFromFormat('d-m-Y H:i', $request->input('sendDateTimeUTC'));

        $message = Message::instance()
            ->title($request->input('title'))
            ->content($request->input('content'))
            ->platforms($request->input('platforms'))
            ->whereIn(
                $baseConfig->pushwooshSetting->channels_tag,
                [$request->input('channel')]
            )
            ->url($request->input('url'))
            ->deliverAt(
                $request->input('delayedSending')
                    ? $deliverAt
                    : 'now'
            );

        if ($request->input('eventContent')) {
            $message->with(
                'pushType',
                $this->mapDistributionType($request->input('eventContent.data.content.distribution_type_id'))
            )
                ->with('videoId', $request->input('eventContent.id'))
                ->with(
                    'isPay',
                    (int) $request->input('eventContent.data.payment.payment_setting_id') === PaymentSetting::PAY
                )
                ->with(
                    'isLivestream',
                    (int) $request->input('eventContent.data.content.distribution_type_id') === DistributionType::LIVE
                );
        }

        $log = PushNotificationLog::create([
            'base_config_id' => $baseConfig->id,
            'title' => $message->getTitle(),
            'content' => $message->getContent(),
            'use_recipient_timezone' => $message->isUsingRecipientTimezone(),
            'shorten_url' => $message->shouldShortenUrl(),
            'url' => $message->getUrl(),
            'pending_at' => now(),
            'deliver_at' => $deliverAt,
        ]);

        $job = new SendPushNotification($message, $log, $baseConfig->pushwooshSetting->application_code);
        if ($request->input('delayedSending')) {
            $job->delay($deliverAt);
        }
        $this->dispatch($job);

        return $this->responseNoContent();
    }

    public function cancel($id)
    {
        return new PushNotificationLogResource(
            $this->pushNotificationLog->cancel($id)
        );
    }

    protected function mapDistributionType(int $distributionTypeId)
    {
        return [
            DistributionType::LIVE => 'live',
            DistributionType::VOD => 'vod',
        ][$distributionTypeId];
    }
}
