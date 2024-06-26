<?php
/**
 * Copyright 2024, Portal89 (https://portal89.com.br)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2024, Portal89 (https://portal89.com.br)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Portal89\OracleDriver\Config;

use Cake\Core\Exception\Exception;
use Cake\Utility\Hash;

/**
 * A trait for reading and writing instance config
 *
 * Implementing objects are expected to declare a `$_defaultConfig` property.
 */
trait ConfigTrait
{
    /**
     * Whether the config property has already been configured with defaults
     *
     * @var bool
     */
    protected $_configInitialized = false;

    /**
     * ### Usage
     *
     * Reading the whole config:
     *
     * ```
     * $this->config();
     * ```
     *
     * Reading a specific value:
     *
     * ```
     * $this->config('key');
     * ```
     *
     * Reading a nested value:
     *
     * ```
     * $this->config('some.nested.key');
     * ```
     *
     * Setting a specific value:
     *
     * ```
     * $this->config('key', $value);
     * ```
     *
     * Setting a nested value:
     *
     * ```
     * $this->config('some.nested.key', $value);
     * ```
     *
     * Updating multiple config settings at the same time:
     *
     * ```
     * $this->config(['one' => 'value', 'another' => 'value']);
     * ```
     *
     * @param string|array|null $key The key to get/set, or a complete array of configs.
     * @param mixed|null $value The value to set.
     * @param bool $merge Whether to recursively merge or overwrite existing config, defaults to true.
     * @return mixed Config value being read, or the object itself on write operations.
     * @throws \Cake\Core\Exception\Exception When trying to set a key that is invalid.
     */
    public function config($key = null, $value = null, $merge = true): array
    {
        if (!$this->_configInitialized) {
            $this->_config = $this->_defaultConfig;
            $this->_configInitialized = true;
        }

        if (is_array($key) || func_num_args() >= 2) {
            $this->_configWrite($key, $value, $merge);

            return $this;
        }

        return $this->_configRead($key);
    }

    /**
     * Write a config variable
     *
     * @param string|array $key Key to write to.
     * @param mixed $value Value to write.
     * @param bool|string $merge True to merge recursively, 'shallow' for simple merge,
     *   false to overwrite, defaults to false.
     * @return void
     * @throws \Cake\Core\Exception\Exception if attempting to clobber existing config
     */
    protected function _configWrite($key, $value, $merge = false)
    {
        if (is_string($key) && $value === null) {
            $this->_configDelete($key);

            return;
        }

        if ($merge) {
            $update = is_array($key) ? $key : [$key => $value];
            if ($merge === 'shallow') {
                $this->_config = array_merge($this->_config, Hash::expand($update));
            } else {
                $this->_config = Hash::merge($this->_config, Hash::expand($update));
            }

            return;
        }

        if (is_array($key)) {
            foreach ($key as $k => $val) {
                $this->_configWrite($k, $val);
            }

            return;
        }

        if (strpos($key, '.') === false) {
            $this->_config[$key] = $value;

            return;
        }

        $update =& $this->_config;
        $stack = explode('.', $key);

        foreach ($stack as $k) {
            if (!is_array($update)) {
                throw new Exception(sprintf('Cannot set %s value', $key));
            }

            if (!isset($update[$k])) {
                $update[$k] = [];
            }

            $update =& $update[$k];
        }

        $update = $value;
    }

    /**
     * Delete a single config key
     *
     * @param string $key Key to delete.
     * @return void
     * @throws \Cake\Core\Exception\Exception if attempting to clobber existing config
     */
    protected function _configDelete($key)
    {
        if (strpos($key, '.') === false) {
            unset($this->_config[$key]);

            return;
        }

        $update =& $this->_config;
        $stack = explode('.', $key);
        $length = is_countable($stack) ? count($stack) : 0;

        foreach ($stack as $i => $k) {
            if (!is_array($update)) {
                throw new Exception(sprintf('Cannot unset %s value', $key));
            }

            if (!isset($update[$k])) {
                break;
            }

            if ($i === $length - 1) {
                unset($update[$k]);
                break;
            }

            $update =& $update[$k];
        }
    }

    /**
     * Read a config variable
     *
     * @param string|null $key Key to read.
     * @return mixed
     */
    protected function _configRead($key)
    {
        if ($key === null) {
            return $this->_config;
        }

        if (strpos($key, '.') === false) {
            return $this->_config[$key] ?? null;
        }

        $return = $this->_config;

        foreach (explode('.', $key) as $k) {
            if (!is_array($return) || !isset($return[$k])) {
                $return = null;
                break;
            }

            $return = $return[$k];
        }

        return $return;
    }

    /**
     * Merge provided config with existing config. Unlike `config()` which does
     * a recursive merge for nested keys, this method does a simple merge.
     *
     * Setting a specific value:
     *
     * ```
     * $this->config('key', $value);
     * ```
     *
     * Setting a nested value:
     *
     * ```
     * $this->config('some.nested.key', $value);
     * ```
     *
     * Updating multiple config settings at the same time:
     *
     * ```
     * $this->config(['one' => 'value', 'another' => 'value']);
     * ```
     *
     * @param string|array $key The key to set, or a complete array of configs.
     * @param mixed|null $value The value to set.
     * @return $this The object itself.
     */
    public function configShallow($key, $value = null)
    {
        if (!$this->_configInitialized) {
            $this->_config = $this->_defaultConfig;
            $this->_configInitialized = true;
        }

        $this->_configWrite($key, $value, 'shallow');

        return $this;
    }
}
