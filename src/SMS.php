<?php


namespace ITPalert\Web2sms;

use InvalidArgumentException;
use RuntimeException;
use DateTime;
use DateTimeInterface;

use ITPalert\Web2sms\GsmCharsetConverter\Converter;

class SMS
{
    public const GSM_7_CHARSET = "\n\f\r !\"\#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_abcdefghijklmnopqrstuvwxyz{|}~ ¡£¤¥§¿ÄÅÆÇÉÑÖØÜßàäåæèéìñòöøùüΓΔΘΛΞΠΣΦΨΩ€";

    /**
     * @var string
     */
    protected string $type = 'text';

    protected string $message = '';

    protected string $nonce = '';

    protected ?string $deliveryReceiptCallback = null;

    protected ?string $schedule = null;

    protected ?string $displayedMessage = null;

    protected ?string $clientRef = null;

    public function __construct(protected string $to, protected string $from, string $message, string $type = 'text')
    {
        $this->setType($type);
        $this->setMessage($message);
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDeliveryReceiptCallback(): ?string
    {
        return $this->deliveryReceiptCallback;
    }

    /**
     * @return $this
     */
    public function setDeliveryReceiptCallback(string $deliveryReceiptCallback): self
    {
        $this->deliveryReceiptCallback = (strlen($deliveryReceiptCallback) > 0) ? $deliveryReceiptCallback : null;

        return $this;
    }

    public function getClientRef(): ?string
    {
        return $this->clientRef;
    }

    /**
     * @return $this
     */
    public function setClientRef(string $clientRef): self
    {
        if (strlen($clientRef) > 40) {
            throw new InvalidArgumentException('Client Ref can be no more than 40 characters');
        }

        $this->clientRef = (strlen($clientRef) > 0) ? $clientRef : null;

        return $this;
    }

    public function getDisplayedMessage(): ?string
    {
        return $this->displayedMessage;
    }

     /**
     * @return $this
     */
    public function setDisplayedMessage(string $displayedMessage): self
    {
        $this->displayedMessage = (strlen($displayedMessage) > 0) ? $displayedMessage : null;

        return $this;
    }

    public function getSchedule(): ?string
    {
        return $this->schedule;
    }

    /**
     * @return $this
     */
    public function setSchedule($schedule): self
    {
        if (! $schedule instanceof DateTimeInterface) {
            $schedule = new DateTime($schedule);
        }

        $this->schedule = $schedule->format('Y-m-d H:i:s');

        return $this;
    }

    public function setMessage(string $message): self
    {
        if ($this->getType() === 'text' && ! self::isGsm7($this->message)) {
            $this->message = (new Converter())->convertUtf8ToGsm($message, true, '?');
        }
        else {
            $this->message = $message;
        }

        return $this;
    }
    
    public function getMessage(): string
    {
        return $this->message;
    }

    public function getNonce(): string
    {
        if(!$this->nonce) {
            $this->nonce = time();
        }

        return $this->nonce;
    }

    protected static function isGsm7(string $message): bool
    {
        $fullPattern = "/\A[" . preg_quote(self::GSM_7_CHARSET, '/') . "]*\z/u";
        return (bool)preg_match($fullPattern, $message);
    }

    public function verifyMessage(): void
    {
        if(is_null($this->getTo()) || empty($this->getTo())) {
            throw new \RuntimeException('INVALID_RECIVER');
        }

        $message = $this->getMessage();

        if(!isset($message) || is_null($message) || empty($message)) {
            throw new \RuntimeException('INVALID_MESSAGE');
        }
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        $data = [
            'sender' => $this->getFrom(),
            'recipient' => $this->getTo(),
            'message' => $this->getMessage(),
            'validityDatetime' => '',
            'nonce' => $this->getNonce(),
        ];

        if (!is_null($this->getDeliveryReceiptCallback())) {
            $data['callbackUrl'] = $this->getDeliveryReceiptCallback();
        }

        if (!is_null($this->getClientRef())) {
            $data['userData'] = $this->getClientRef();
        }

        if (!is_null($this->getDisplayedMessage())) {
            $data['visibleMessage'] = $this->getDisplayedMessage();
        }

        if (!is_null($this->getSchedule())) {
            $data['scheduleDatetime'] = $this->getSchedule();
        }

        return $data;
    }
}
