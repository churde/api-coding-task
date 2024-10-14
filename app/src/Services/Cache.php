<?php
namespace App\Services;

use Predis\Client;
use App\Interfaces\CacheInterface;

class Cache implements CacheInterface
{
    private Client $redis;

    public function __construct()
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => 6379,
        ]);
    }

    public function get($key)
    {
        $data = $this->redis->get($key);
        return $data ? json_decode($data, true) : null;
    }

    public function set($key, $value, $expiration = 3600)
    {
        $this->redis->setex($key, $expiration, json_encode($value));
    }

    public function delete($key)
    {
        $this->redis->del($key);
    }

    public function flush()
    {
        $this->redis->flushall();
    }
}
