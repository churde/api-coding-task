<?php

namespace App\Interfaces;

interface CacheInterface
{
    public function get($key);
    public function set($key, $value, $expiration = 3600);
    public function delete($key);
    public function flush();
}
