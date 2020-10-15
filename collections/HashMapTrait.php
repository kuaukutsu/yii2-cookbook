<?php

namespace cookbook\collections;

/**
 * Trait HashMapTrait
 *
 * Добавляет структуру типа hash map для хранения рассчитываемых данных.
 *
 * ```php
 * public $isEmpty = false;
 *
 * public function getValues(string $arg): array
 * {
 *  return $this->cacheHash(
 *      $this->generateHashKey(__METHOD__, $arg),
 *      static function (self $context): array {
 *          return $context->isEmpty ? [] : [1,2,3,4];
 *      }
 *  );
 * }
 * ```
 */
trait HashMapTrait
{
    /**
     * Имя выбранного алгоритма хеширования (например, "md5", "sha256", "haval160,4" и т.д.)
     * @see https://www.php.net/manual/ru/function.hash.php
     *
     * @var string
     */
    protected $hashKeyAlgo = 'crc32';

    /**
     * Storage.
     * @var array
     */
    private $hashMap = [];

    /**
     * Функция $call должна возвращать значение, которое должно быть сохранено.
     *
     * @param string $key
     * @param callable $call
     * @return mixed
     */
    protected function cacheHash(string $key, callable $call)
    {
        if (!($this->hashMap[$key] ?? false)) {
            $this->hashMap[$key] = $call($this);
        }

        return $this->hashMap[$key];
    }

    /**
     * На основе аргументов генерирует ключ-строку.
     *
     * @param mixed ...$args
     * @return string
     */
    protected function generateHashKey(...$args): string
    {
        return hash($this->hashKeyAlgo, implode(':', $args));
    }
}
