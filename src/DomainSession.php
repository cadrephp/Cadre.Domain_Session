<?php
declare(strict_types=1);
namespace Cadre\Domain_Session;

use DateInterval;
use DateTime;
use DateTimeZone;

class DomainSession implements DomainSessionInterface
{
    protected $id;
    protected $data;
    protected $created;
    protected $updated;
    protected $expires;

    public function __construct(
        DomainSessionId $id,
        array $data,
        DateTime $created,
        DateTime $updated,
        DateTime $expires
    ) {
        $this->id = $id;
        $this->data = $data;
        $this->created = $created;
        $this->updated = $updated;
        $this->expires = $expires;
    }

    public static function withId(DomainSessionId $id, $interval = 'PT3M')
    {
        $created = $updated = new DateTime('now', new DateTimeZone('UTC'));
        $expires = clone ($updated);
        $expires->add(new DateInterval($interval));
        $session = new static($id, [], $created, $updated, $expires);
        return $session;
    }

    public function all()
    {
        return $this->data;
    }

    public function get(string $key, $default = null)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function set(string $key, $val)
    {
        $this->markAsUpdated();
        $this->data[$key] = $val;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function remove(string $key)
    {
        $this->markAsUpdated();
        unset($this->data[$key]);
    }

    public function id(): DomainSessionId
    {
        return $this->id;
    }

    public function created(): DateTime
    {
        return clone $this->created;
    }

    public function updated(): DateTime
    {
        return clone $this->updated;
    }

    public function expires(): DateTime
    {
        return clone $this->expires;
    }

    public function renew($interval = 'PT3M')
    {
        $this->markAsUpdated();
        $this->expires = clone $this->updated;
        $this->expires->add(new DateInterval($interval));
    }

    public function isExpired(DateTime $when = null): bool
    {
        if (is_null($when)) {
            $when = new DateTime('now', new DateTimeZone('UTC'));
        }

        return ($this->expires <= $when);
    }

    protected function markAsUpdated()
    {
        $this->updated = new DateTime('now', new DateTimeZone('UTC'));
    }
}
