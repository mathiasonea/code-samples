<?php


/**
 * 
 * TAKEN FROM https://github.com/laravel-notification-channels/pushwoosh/blob/master/src/PushwooshMessage.php
 * AND MODIFIED TO THE CUSTOMERS NEEDS
 * 
 */
namespace App\Services\Pushwoosh;

use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

class Message
{
    protected $androidRootParameters;
    protected $iosRootParameters;
    protected $data;
    protected $title;
    protected $content;
    protected $identifier;
    protected $preset;
    protected $recipientTimezone;
    protected $shortenUrl;
    protected $timezone;
    protected $throughput;
    protected $url;
    protected $when;
    protected $conditions;
    protected $conditionsOperator;
    protected $platforms;

    public function __construct(string $content = '')
    {
        $this->content = $content;
        $this->recipientTimezone = false;
        $this->when = 'now';
        $this->conditionsOperator = 'AND';
    }
    
    public static function instance(string $content = ''): Message
    {
        return new static($content);
    }
    
    /**
     * Set the message content.
     *
     * @param  string  $content
     * @param  string|null  $language
     * @return Message
     */
    public function content(string $content, string $language = null): Message
    {
        if ($language) {
            if (!is_array($this->content)) {
                $this->content = [];
            }
            
            $this->content[$language] = $content;
        } else {
            $this->content = $content;
        }
        
        return $this;
    }
    
    /**
     * Set the delivery moment.
     *
     * @param  \DateTimeInterface|string  $when
     * @param  \DateTimeZone|string|null  $timezone
     * @return Message
     */
    public function deliverAt($when, $timezone = null): Message
    {
        if ($when instanceof DateTimeInterface) {
            $timezone = $when->getTimezone();
            $when = $when->format('Y-m-d H:i');
        }
        
        if ($timezone instanceof DateTimeZone) {
            $timezone = $timezone->getName();
        }
        
        $this->timezone = $timezone;
        $this->when = $when;
        
        return $this;
    }
    
    public function identifier(string $identifier): Message
    {
        $this->identifier = $identifier;
        
        return $this;
    }
    
    public function preset(string $preset): Message
    {
        $this->preset = $preset;
        
        return $this;
    }
    
    public function throttle(int $limit): Message
    {
        $this->throughput = max(100, min($limit, 1000));
        
        return $this;
    }
    
    /**
     * Set the URL the message should link to.
     *
     * @param  string  $url
     * @param  bool  $shorten
     * @return Message
     */
    public function url(?string $url, bool $shorten = true): Message
    {
        $this->shortenUrl = $shorten;
        $this->url = $url;
        
        return $this;
    }
    
    public function useRecipientTimezone(): Message
    {
        $this->recipientTimezone = true;
        
        return $this;
    }
    
    /**
     * Set a condition to restrict the recipients of the notification
     * @see https://docs.pushwoosh.com/platform-docs/api-reference/messages#tag-conditions
     *
     * @param  string  $tag
     * @param  array  $needles
     * @return Message
     */
    public function whereIn(string $tag, array $needles): Message
    {
        $needles = array_map([$this, 'mapNeedles'], $needles);
        
        $this->conditions[] = [
            $tag,
            'IN',
            $needles,
        ];
        
        return $this;
    }

    protected function mapNeedles($needle): string
    {
        return (string) $needle;
    }
    
    /**
     * Set the conditions operator via the API to 'OR'
     *
     * @return Message
     */
    public function optionalConditions(): Message
    {
        $this->conditionsOperator = 'OR';
        
        return $this;
    }

    public function toArray(): array
    {
        $payload = [
            'android_root_params' => $this->androidRootParameters,
            'ios_root_params' => $this->iosRootParameters,
            'data' => $this->data,
            'send_date' => $this->when,
            'content' => $this->content,
            'ignore_user_timezone' => !$this->recipientTimezone,
            'link' => $this->url,
            'minimize_link' => $this->url ? $this->shortenUrl : null,
            'preset' => $this->preset,
            'send_rate' => $this->throughput,
            'transactionId' => $this->identifier,
            'timezone' => $this->timezone,
            'conditions' => $this->conditions,
            'conditions_operator' => $this->conditionsOperator,
            'chrome_title' => $this->title,
            'firefox_title' => $this->title,
            'safari_title' => $this->title,
            'ios_title' => $this->title,
            'platforms' => $this->platforms,
            'android_banner' => $this->androidRootParameters['banner'] ?? null,
            'android_header' => $this->title,
        ];
        
        return array_filter($payload, [$this, 'isNotNull']);
    }

    protected function isNotNull($value): bool {
        return $value !== null;
    }

    public function title(?string $title)
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function isUsingRecipientTimezone(): bool
    {
        return (bool) $this->recipientTimezone;
    }

    public function shouldShortenUrl(): bool
    {
        return (bool) $this->shortenUrl;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getDelivery()
    {
        return $this->when;
    }

    public function platforms(array $platforms)
    {
        $this->platforms = $platforms;

        return $this;
    }

    /**
     * Add a root level parameter.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $platform
     * @return $this
     */
    public function with(string $key, $value, string $platform = null)
    {
        if (!in_array($platform, [null, 'ios', 'android'])) {
            throw new InvalidArgumentException("Invalid platform {$platform}");
        }

        if (($platform ?: 'android') === 'android') {
            $this->androidRootParameters[$key] = $value;
            $this->data[$key] = $value;
        }

        if (($platform ?: 'ios') === 'ios') {
            $this->iosRootParameters[$key] = $value;
        }

        return $this;
    }

}
