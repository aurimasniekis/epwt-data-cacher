<?php

namespace EPWT\DataCacher;

use EPWT\DataCacher\Driver\DriverInterface;

/**
 * Class DataCacher
 *
 * @package EPWT\DataCacher
 * @author Aurimas Niekis <aurimas.niekis@gmail.com>
 */
class DataCacher
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var mixed
     */
    protected $dataProvider;

    /**
     * @var array
     */
    protected $ignoredMethods;

    /**
     * @var array
     */
    protected $statistics;

    public function __construct()
    {
        $this->statistics = [];
        $this->statistics['ignored_methods'] = [];
        $this->statistics['methods'] = [];
    }

    /**
     * @param mixed $object
     *
     * @return DataCacher
     */
    public static function wrapObject($object)
    {
        $dataCacher = new self();
        $dataCacher->setDataProvider($object);

        return $dataCacher;
    }

    /**
     * @return array
     */
    public function getIgnoredMethods()
    {
        return [];
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return array
     */
    public function keyDefault($method, array $arguments = [])
    {
        array_unshift($arguments, $method);

        return $arguments;
    }

    public function ttlDefault($method, array $arguments = [])
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function __call($name, $arguments)
    {
        $startTime = microtime(true);
        if (method_exists($this, $name)) {
            return call_user_func_array(
                [$this, $name],
                $arguments
            );
        }

        if (method_exists($this->getDataProvider(), $name)) {
            if (null === $this->ignoredMethods) {
                $this->ignoredMethods = array_flip($this->getIgnoredMethods());
            }

            if (isset($this->ignoredMethods[$name])) {
                return call_user_func_array(
                    [$this->dataProvider, $name],
                    $arguments
                );
            }

            $keyMethodName = 'key' . ucfirst($name);
            if (method_exists($this, $keyMethodName)) {
                $key = call_user_func(
                    [$this, $keyMethodName],
                    $name,
                    $arguments
                );
            } else {
                $key = $this->keyDefault($name, $arguments);
            }

            $ttlMethodName = 'ttl' . ucfirst($name);
            if (method_exists($this, $ttlMethodName)) {
                $ttl = call_user_func(
                    [$this, $ttlMethodName],
                    $name,
                    $arguments
                );
            } else {
                $ttl = $this->ttlDefault($name, $arguments);
            }

            $key = $this->getDriver()->buildKey($key);
            $data = $this->getDriver()->get($key);

            if (false === $data) {
                $data = call_user_func_array(
                    [$this->dataProvider, $name],
                    $arguments
                );

                $this->getDriver()->set($key, $data, $ttl);
                $this->statCacheMiss($key, $startTime);

                return $data;
            }

            $this->statCacheHit($key, $startTime);

            return $data;
        }
    }

    /**
     * @return DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @param DriverInterface $driver
     *
     * @return $this
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDataProvider()
    {
        return $this->dataProvider;
    }

    /**
     * @param mixed $dataProvider
     *
     * @return $this
     */
    public function setDataProvider($dataProvider)
    {
        $this->dataProvider = $dataProvider;

        return $this;
    }

    /**
     * @return array
     */
    public function getStatistics()
    {
        return $this->statistics;
    }

    /**
     * @param string $name
     */
    protected function statIgnoredMethod($name)
    {
        if (isset($this->statistics['ignored_methods'][$name])) {
            $this->statistics['ignored_methods'][$name]++;
        }

        $this->statistics['ignored_methods'][$name] = 1;
    }

    /**
     * @param string $key
     * @param float $startTime
     */
    protected function statCacheMiss($key, $startTime)
    {
        $this->statistics['methods'][$key][] = ['status' => 'miss', 'time' => microtime(true) - $startTime];
    }

    /**
     * @param string $key
     * @param float $startTime
     */
    protected function statCacheHit($key, $startTime)
    {
        $this->statistics['methods'][$key][] = ['status' => 'hit', 'time' => microtime(true) - $startTime];
    }
}
