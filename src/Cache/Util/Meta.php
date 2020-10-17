<?php

namespace Nyrados\Translator\Cache\Util;

use DateInterval;
use DateTime;

class Meta
{
    /** @var DateTime */
    private $expires;

    /** @var array<string> */
    private $groups = [];

    /** @var array<string> */
    private $keys = [];

    /** @var string */
    private $checksum = [];

    public function __construct()
    {
        $this->expires = new DateTime();
    }

    public function isExpired(): bool
    {
        return $this->expires->getTimestamp() < (new DateTime())->getTimestamp();
    }

    public function containsSame(string $checksum): bool
    {
        return $this->checksum === $checksum;
    }

    public function getSingleKeys(): array
    {
        return $this->keys;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function toArray(): array
    {
        return [
            'e' => $this->expires->getTimestamp(),
            'c' => $this->checksum,
            'k' => $this->keys,
            'g' => $this->groups
        ];
    }

    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->expires->setTimestamp($data['e']);
        $self->checksum = $data['c'];
        $self->keys = $data['k'];
        $self->groups = $data['g'];

        return $self;
    }

    public static function fromRequestCache(RequestCache $cache, DateInterval $expires): self
    {
        $self = new self();
        $self->expires->add($expires);
        $self->checksum = $cache->getChecksum();
        $self->keys = $cache->getKeys();
        $self->groups = array_keys($cache->getDependedGroups());

        return $self;
    }
}