<?php

namespace cookbook\dto;

use ReflectionClass;
use ReflectionException;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * Class Hydrator
 *
 * Example:
 *
 * ```php
 * $data = [];
 *
 * $dtoHydrator = new Hydrator([
 *  'id' => 'guid',
 *  'name' => 'owner.0._name',
 *  'parent_id' => 'parent.id',
 * ]);
 *
 * $item = $dtoHydrator->hydrate($data, ModelDTO::class);
 * ```
 *
 */
class Hydrator
{
    /**
     * Mapping
     *
     * @var array массив пересечения схем между насыщаемым объектов и данными.
     */
    private $map;

    /**
     * @var array массив свойств объекта которые были найдены в массиве данных.
     */
    private $fields = [];

    /**
     * @var string
     */
    private $hashStub;

    /**
     * Hydrator constructor.
     *
     * @param array $map может быть:
     * - ассоциативным массивом (слева: свойство объекта; справа: путь до данных в массиве)
     * - плоским массивом, тогда считам что свойства объекта, есть и путь до данных в массиве
     */
    public function __construct(array $map)
    {
        $this->map = [];
        foreach ($map as $keyTo => $keyFrom) {
            if (is_int($keyTo)) {
                $keyTo = $keyFrom;
            }

            $this->map[$keyTo] = $keyFrom;
        }

        $this->hashStub = hash('crc32', serialize($map));
    }

    /**
     * @param array $data массив с данными
     * @param string $className имя класса, на основе которого будет создан объект
     * @return null|object null если что-то пошло не так
     */
    public function hydrate(array $data, string $className)
    {
        try {
            $reflection = new ReflectionClass($className);
            $object = $reflection->newInstanceWithoutConstructor();
            foreach ($this->map as $dataKey => $propertyValue) {
                $value = $this->getValue($dataKey, $propertyValue, $data);

                if ($reflection->hasProperty($dataKey)) {
                    $property = $reflection->getProperty($dataKey);
                    $property->setAccessible(true);
                    $property->setValue($object, $value);
                } else {
                    // if has not method when setter
                    if (!$reflection->hasMethod($dataKey)) {
                        $dataKey = 'set' . $dataKey;
                    }

                    if ($reflection->hasMethod($dataKey)) {
                        $method = $reflection->getMethod($dataKey);
                        $method->invoke($object, $value);
                    }
                }
            }

            return $object;

        } catch (ReflectionException $exception) {
            Yii::error($exception->getMessage(), __METHOD__);
        }

        return null;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param string $key
     * @param string|callable $value
     * @param array $data
     * @param null|mixed $default
     * @return mixed|null
     */
    private function getValue(string $key, $value, array $data, $default = null)
    {
        if (is_object($value) && is_callable($value)) {
            $this->fields[] = $key;
            return $value($data);
        }

        /**
         * Фокус: если по обычному ключу в массиве данных нет значений или null, то пробуем
         * найти ключ изменить на camelCase и поискать ещё раз, но здесь,
         * либо ключ найден и тогда мы вернём значение, если нет, то вернём кеш заглушку,
         * тем самым отмечаем что ключ массива соответсвует свойству, либо не найден.
         */
        $valueHash = ArrayHelper::getValue($data, $value)
            ?? ArrayHelper::getValue($data, Inflector::variablize($value), $this->hashStub);

        if ($valueHash !== $this->hashStub) {
            $this->fields[] = $key;
            return $valueHash;
        }

        return $default;
    }
}
