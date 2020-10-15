<?php

namespace cookbook\collections;

use ArrayIterator;
use Ds\Collection;
use IteratorAggregate;
use RuntimeException;
use Traversable;
use Yii;
use yii\helpers\ArrayHelper;
use yii\rest\Serializer;

/**
 * Class BaseModelCollection
 *
 * Базовый класс для реализации коллекций.
 */
abstract class BaseModelCollection implements IteratorAggregate, Collection
{
    use HashMapTrait;

    /**
     * @var string|array the configuration for creating the serializer that formats the response data.
     */
    protected $serializer = Serializer::class;

    /**
     * Мапа коллекции.
     *
     * @var array
     */
    private $collection = [];

    /**
     * Тип коллеции, get_class($item)
     *
     * @return string
     */
    abstract protected function getType(): string;

    /**
     * BaseModelCollection constructor.
     * @param mixed ...$items
     */
    public function __construct(...$items)
    {
        foreach ($items as $item) {
            $this->attach($item);
        }
    }

    /**
     * Adds an object in the storage
     * @param object $object The object to add.
     * @return void
     */
    public function attach($object): void
    {
        if (is_a($object, $this->getType())) {
            $this->collection[] = $object;
            return;
        }

        throw new RuntimeException('Элемент коллекции должен быть экземпляром типа ' . $this->getType());
    }

    /**
     * @param BaseModelCollection $collection
     * @return static
     */
    public function merge(self $collection): self
    {
        foreach ($collection as $item) {
            $this->collection[] = $item;
        }

        return $this;
    }

    /**
     * Removes an object from the storage
     * @param object $object The object to add.
     * @return void
     */
    public function detach($object): void
    {
        $this->collection = array_filter($this->collection, static function ($item) use ($object) {
            return $item !== $object;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->collection = [];
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return count($this->collection);
    }

    /**
     * {@inheritDoc}
     */
    public function copy(): Collection
    {
        return clone $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->collection);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_map(function ($item) {
            return Yii::createObject($this->serializer)->serialize($item);
        }, $this->collection);
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Обертка над usort.
     *
     * @param callable $fncSorting
     */
    public function usort(callable $fncSorting): void
    {
        usort($this->collection, $fncSorting);
    }

    /**
     * Возвращает массив из значений одного столбца коллекции.
     *
     * @param string $key
     * @return array
     */
    public function column(string $key): array
    {
        return array_column($this->collection, $key);
    }

    /**
     * Получает элемент коллекции по ключу.
     * В качестве ключа используется значение свойства элемента коллекции.
     * При первом запросе строится карта соответствия ключей и свойств, относительно заданного свойства (path)
     *
     * @param mixed $key значение ключа
     * @param string $path путь до значения, по которому будет группировка, @see ArrayHelper::getValue()
     * @param mixed|null $default значение которое необходимо вернуть если ключ не найден в коллекции.
     * @return mixed|null
     */
    public function getItem($key, string $path, $default = null)
    {
        $map = $this->getMapIndex($path);
        return $this->collection[$map[$key] ?? null] ?? $default;
    }

    /**
     * Группирует коллекцию в массив с значением ключа из path.
     * Разварачивает коллекцию в массив, где ключи задаются значениями из коллекции.
     *
     * @param string $path путь до значения, по которому будет группировка, @see ArrayHelper::getValue()
     * @return array
     */
    public function indexBy(string $path): array
    {
        $groups = [];
        foreach ($this->collection as $item) {
            $groups[ArrayHelper::getValue($item, $path, 0)] = $item;
        }

        return $groups;
    }

    /**
     * Группирует коллекцию по значению полученому из path, с возможной коррекцией через prepareGroupKey
     *
     * @param string $path путь до значения, по которому будет группировка, @see ArrayHelper::getValue()
     * @param callable|null $prepareGroupKey функция которая на вход получает текущее значение группировки,
     * возвращает конечное/измененное значение ключа группировки.
     *
     * @return array
     */
    public function groupBy(string $path, callable $prepareGroupKey = null): array
    {
        /** @var static[] $groups */
        $groups = [];
        foreach ($this->collection as $item) {
            $groupKey = ArrayHelper::getValue($item, $path, 0) ?? 0;
            if ($prepareGroupKey) {
                $groupKey = $prepareGroupKey($groupKey);
            }

            if (isset($groups[$groupKey])) {
                $groups[$groupKey]->attach($item);
            } else {
                $groups[$groupKey] = new static($item);
            }
        }

        return $groups;
    }

    /**
     * Генерирует карту соответствия свойств элементов коллекии их ключам в списке коллекции.
     *
     * @param string $path
     * @return array
     */
    private function getMapIndex(string $path): array
    {
        return $this->cacheHash(
            $this->generateHashKey(__METHOD__, $path),
            static function (self $context) use ($path) {
                return array_flip($context->column($path));
            }
        );
    }
}
