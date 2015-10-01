<?php

require_once './vendor/autoload.php';


use EPWT\DataCacher\DataCacher;
use EPWT\DataCacher\Driver\RedisDriver;
/**
 * Class Data
 *
 * @package AppBundle\Data\Provider
 * @author Aurimas Niekis <aurimas.niekis@gmail.com>
 */

class Data
{
    public function getAll()
    {
        return [
            1 => 'a',
            2 => 'b',
            3 => 'c',
            4 => 'd',
        ];
    }

    public function get($id)
    {
        $all = $this->getAll();

        return $all[$id];
    }
}

$data = new Data();

$redis = new \Redis();
$redis->connect('127.0.0.1');

$redisDriver = new RedisDriver();
$redisDriver->setRedis($redis);

$cacher = DataCacher::wrapObject($data);
$cacher->setDriver($redisDriver);

for ($i = 0; $i < 200; $i++) {
    if (mt_rand(1,100) % 2 == 0) {
        $redisDriver->flush();
    }

    $cacher->get(2);
}

$stats = $cacher->getStatistics();

$statsByMethod = [];

class RenderTime{
    const PRECISION_SECOND = 0;
    const PRECISION_MILLISECOND = 1;
    const PRECISION_MICROSECOND = 2;


    /**
     * This function return the time the code use to process
     * @param $precision the precision wanted, with const. second, millisecond and microsecond available (default PRECISION_SECOND)
     * @param $floatingPrecision the number of numbers after the floating point (default 0)
     * @param $showUnit precise if the unit should be returned (default true)
     * @return the render time in the precision asked. Note that the precision is ±0.5 the precision (eq. 5s is at least 4.5s and at most 5.5s) <br/>
     * The code have an error about 2 or 3µs (time to execute the end function)
     */
    public function getRenderTime($time, $precision = self::PRECISION_SECOND, $floatingPrecision = 0, $showUnit = true){

            $duration = round(($time) * 10 ** ($precision * 3), $floatingPrecision);

            if($showUnit)
                return $duration.' '.self::getUnit($precision);
            else
                return $duration;
    }

    private static function getUnit($precision){
        switch($precision){
            case self::PRECISION_SECOND :
                return 's';
            case self::PRECISION_MILLISECOND :
                return 'ms';
            case self::PRECISION_MICROSECOND :
                return 'µs';
            default :
                return '(no unit)';
        }
    }
}

foreach ($stats['methods'] as $methodName => $stats) {
    foreach ($stats as $stat) {
        if (false ===isset($statsByMethod[$methodName])) {
            $statsByMethod[$methodName] = ['miss' => 0, 'hit' => 0, 'calls' => 0, 'avg_time' => 0, 'min_time' => $stat['time'], 'max_time' => 0];
        }

        if ($stat['status'] == 'miss') {
            $statsByMethod[$methodName]['miss']++;
        } else {
            $statsByMethod[$methodName]['hit']++;
        }

        if ($statsByMethod[$methodName]['min_time'] > $stat['time']) {
            $statsByMethod[$methodName]['min_time'] = $stat['time'];
        }

        if ($statsByMethod[$methodName]['max_time'] < $stat['time']) {
            $statsByMethod[$methodName]['max_time'] = $stat['time'];
        }

        $statsByMethod[$methodName]['calls']++;
        $statsByMethod[$methodName]['avg_time'] += $stat['time'];
    }
}

$renderTime = new RenderTime();

foreach ($statsByMethod as &$stats) {
    $stats['avg_time'] = $renderTime->getRenderTime($stats['avg_time'] / $stats['calls'], RenderTime::PRECISION_MICROSECOND);
    $stats['min_time'] = $renderTime->getRenderTime($stats['min_time'], RenderTime::PRECISION_MICROSECOND);
    $stats['max_time'] = $renderTime->getRenderTime($stats['max_time'], RenderTime::PRECISION_MICROSECOND);
}

var_dump($statsByMethod);
