<?php

namespace EPWT\DataCacher\Driver;

use EPWT\Traits\SerializerTrait;

/**
 * Class RedisDriver
 * @package EPWT\DataCacher\Driver
 * @author Aurimas Niekis <aurimas.niekis@gmail.com>
 */
class RedisDriver implements DriverInterface
{
    use SerializerTrait;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * @param \Redis $redis
     *
     * @return $this
     */
    public function setRedis($redis)
    {
        $this->redis = $redis;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        $data = $this->getRedis()->get($key);

        if (false !== $data) {
            $data = $this->phpUnserialize($data);
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $data, $ttl = 0)
    {
        $data = $this->phpSerialize($data);

        if ($ttl > 0) {
            return $this->getRedis()->setex($key, $ttl, $data);
        }

        return $this->getRedis()->set($key, $data);
    }

    /**
     * @inheritDoc
     */
    public function remove($key)
    {
        return $this->getRedis()->del($key);
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        return $this->getRedis()->flushDB();
    }

    /**
     * @inheritDoc
     */
    public function buildKey(array $keyParts = [])
    {
        return implode(':', $keyParts);
    }
}
