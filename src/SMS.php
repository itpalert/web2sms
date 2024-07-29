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

    protected ?string $deliveryReceiptCallback = '';

    protected bool $requestDeliveryReceipt = true;

    protected ?string $schedule = '';

    protected string $nonce = '';

    protected bool $visible = true;

    protected string $clientRef = '';

    public function __construct(protected string $to, protected string $from, protected string $message, string $type = 'text')
    {
        $this->setType($type);
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

    public function setType(string $type): SMS
    {
        $this->type = $type;

        return $this;
    }

    public function getRequestDeliveryReceipt(): bool
    {
        return $this->requestDeliveryReceipt;
    }

    /**
     * @return $this
     */
    public function setRequestDeliveryReceipt(bool $requestDeliveryReceipt): self
    {
        $this->requestDeliveryReceipt = $requestDeliveryReceipt;

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
        $this->deliveryReceiptCallback = $deliveryReceiptCallback;
        $this->setRequestDeliveryReceipt(true);

        return $this;
    }

    public function getClientRef(): string
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

        $this->clientRef = $clientRef;

        return $this;
    }

    public function getVisible(): bool
    {
        return $this->visible;
    }

     /**
     * @return $this
     */
    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    public function getSchedule(): string
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

    public function getMessage(): string
    {
        if ($this->getType() === 'text' && ! self::isGsm7($this->message)) {
            return (new Converter())->convertUtf8ToGsm($this->message, true, '?');
        }

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

        if ($this->getRequestDeliveryReceipt() && !is_null($this->getDeliveryReceiptCallback())) {
            $data['callbackUrl'] = $this->getDeliveryReceiptCallback();
        }

        if ($this->clientRef) {
            $data['userData'] = $this->getClientRef();
        }

        if ($this->visible) {
            $data['visibleMessage'] = $this->getVisible();
        }

        if ($this->schedule) {
            $data['scheduleDatetime'] = $this->getSchedule();
        }

        return $data;
    }
}