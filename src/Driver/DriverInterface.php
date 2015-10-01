<?php

namespace EPWT\DataCacher\Driver;

/**
 * Interface DriverInterface
 * @package EPWT\DataCacher\Driver
 * @author Aurimas Niekis <aurimas.niekis@gmail.com>
 */
interface DriverInterface
{
    /**
     * @param string $key
     *
     * @return mixed|bool
     */
    public function get($key);

    /**
     * @param string $key
     * @param mixed $data
     * @param int $ttl
     *
     * @return bool
     */
    public function set($key, $data, $ttl = 0);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function remove($key);

    /**
     * @return bool
     */
    public function flush();

    /**
     * @param array $keyParts
     *
     * @return string
     */
    public function buildKey(array $keyParts = []);
}
