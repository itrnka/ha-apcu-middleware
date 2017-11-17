<?php
declare(strict_types=1);

namespace ha\Middleware\Cache\APCu;

use ha\Component\Configuration\Configuration;
use ha\Middleware\Cache\Cache;
use ha\Middleware\MiddlewareDefaultAbstract;
use ha\Middleware\MiddlewareSingletonTrait;

class APCu extends MiddlewareDefaultAbstract implements Cache
{

    use MiddlewareSingletonTrait;

    /** @var int */
    private $defaultTTL = 0;

    /** @var string */
    private $keyPrefix;

    /**
     * APCu constructor.
     *
     * @param Configuration $configuration
     *
     * @throws \ErrorException
     */
    public function __construct(Configuration $configuration)
    {
        // singleton simulation
        $this->denyMultipleInstances();

        // configure
        $this->keyPrefix = $configuration->get('keyPrefix');
        if (!is_string($this->keyPrefix) || $this->keyPrefix === '') {
            throw new \ErrorException('Key prefix from configuration must be not empty string@' . __METHOD__);
        }
        $this->defaultTTL = $configuration->get('defaultTTL');
        if (!is_int($this->defaultTTL) || $this->defaultTTL < 0) {
            throw new \ErrorException('TTL from configuration must be integer>=0@' . __METHOD__);
        }
        // middleware functionality
        parent::__construct($configuration);
    }

    /**
     * Create new record.
     *
     * @param string $key
     * @param mixed $value
     * @param int $TTL
     * @param bool $overwriteOldValue
     *
     * @return Cache
     */
    public function add(string $key, $value, int $TTL = 0, bool $overwriteOldValue = true): Cache
    {
        if ($TTL < 0) {
            $TTL = $this->defaultTTL;
        }
        $realKey = $this->_internalKey($key);
        if ($overwriteOldValue === false) {
            apcu_add($realKey, $value, $TTL);
        }
        else {
            apcu_store($realKey, $value, $TTL);
        }
        return $this;
    }

    /**
     * Delete value from storage by key.
     *
     * @param string $key
     *
     * @return Cache
     */
    public function delete(string $key): Cache
    {
        apcu_delete($this->_internalKey($key));
        return $this;
    }

    /**
     * Delete values from storage by multiple string keys provided in array.
     *
     * @param array $keys
     *
     * @return Cache
     */
    public function deleteMulti(array $keys): Cache
    {
        apcu_delete($this->_internalKeys($keys));
        return $this;
    }

    /**
     * Get record value from storage by key.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed Stored value or default value
     */
    public function get(string $key, $default = null)
    {
        $realKey = $this->_internalKey($key);
        if ($this->has($key)) {
            return apcu_fetch($realKey);
        }
        return $default;
    }

    /**
     * Get record values from storage by keys provided in array.
     *
     * @param array $keys List of keys
     * @param mixed $default Default value if value for key is not found
     *
     * @return array ['key' => val, 'key' => val, ...]
     */
    public function getMulti(array $keys, $default = null): array
    {
        $return = [];
        foreach ($keys AS $key) {
            $return[$key] = $default;
        }
        $trimKeyFrom = strlen($this->keyPrefix);
        foreach (apcu_fetch($this->_internalKeys($keys)) AS $realKey => $value) {
            $key = substr($realKey, $trimKeyFrom);
            $return[$key] = $value;
        }
        return $return;
    }

    /**
     * Determine whether storage has record stored under provided key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return apcu_exists($this->_internalKey($key));
    }

    /**
     * Change record value and TTL in storage by key.
     *
     * @param string $key
     * @param $value
     * @param int $TTL
     * @param bool $autoInsertIfNotFound
     *
     * @return Cache
     */
    public function set(string $key, $value, int $TTL = 0, bool $autoInsertIfNotFound = true): Cache
    {
        if ($TTL < 0) {
            $TTL = $this->defaultTTL;
        }
        $realKey = $this->_internalKey($key);
        apcu_store($realKey, $value, $TTL);
        return $this;
    }

    /**
     * Convert key to keys with instance/app specific prefix
     *
     * @param string $key
     *
     * @return string
     */
    private function _internalKey(string $key): string
    {
        if (!is_string($key) || $key === '') {
            throw new \LogicException('Key must be an nonempty string@' . __METHOD__);
        }
        return $this->keyPrefix . $key;
    }

    /**
     * Convert keys to keys with instance/app specific prefix
     *
     * @param array $keys
     *
     * @return array Remapped keys
     */
    private function _internalKeys(array $keys): array
    {
        $realKeys = [];
        foreach ($keys AS $key) {
            $realKeys[] = $this->_internalKey($key);
        }
        return $realKeys;
    }
}