<?php

namespace Amp\Injector;

final class ReflectionCacheApc implements ReflectionCache
{
    private ReflectionCache $localCache;
    private int $timeToLive = 5;

    public function __construct(ReflectionCache $localCache = null)
    {
        $this->localCache = $localCache ?? new ReflectionCacheArray;
    }

    public function setTimeToLive(int $seconds): self
    {
        if ($seconds <= 0) {
            throw new \Error("Invalid TTL: ${seconds}");
        }

        $this->timeToLive = $seconds;

        return $this;
    }

    /**
     * @return mixed
     */
    public function fetch(string $key)
    {
        $localData = $this->localCache->fetch($key);

        if ($localData != false) {
            return $localData;
        }

        $success = null; // stupid by-ref parameter that scrutinizer complains about
        $data = \apc_fetch($key, $success);

        return $success ? $data : false;
    }

    /**
     * @param mixed $data
     */
    public function store(string $key, $data): void
    {
        $this->localCache->store($key, $data);

        \apc_store($key, $data, $this->timeToLive);
    }
}
