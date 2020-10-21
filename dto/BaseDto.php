<?php

namespace cookbook\dto;

use yii\base\ArrayableTrait;
use yii\base\BaseObject;
use cookbook\dto\Hydrator;

/**
 * Class BaseDto
 *
 * Базовый класс для DTO.
 * DTO простой класс для обмена данными между компонентами. Не должно быть никакой бизнес логики.
 *
 * BaseObject необходим из-за get/set для обратной совместимости.
 */
abstract class BaseDto extends BaseObject implements DtoInterface
{
    use ArrayableTrait;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * Иcпользовать вместо __counstruct()
     *
     * @param array $data данные которыми необходимо заполнить экземпляр объекта
     * @param array $map по умолчанию будет генерироваться на основе полей DTO
     * @return static
     */
    public static function hydrate(array $data, array $map = []): DtoInterface
    {
        if ($map === []) {
            $map = (new static())->fields();
        }

        $hydrator = new Hydrator($map);

        /** @var static $model */
        $model = $hydrator->hydrate($data, static::class);
        $model->fields = $hydrator->getFields();

        return $model;
    }

    /**
     * Для того чтобы свойства объектов DTO выбирались автоматически,
     * необходимо чтобы область видимости была PROTECTED.
     * В противном случае необходимо в классе DTO:
     * - либо копировать данный метод
     * - либо ручками заполнять список полей
     *
     * @return array
     */
    public function fields(): array
    {
        return count($this->fields) ? $this->fields : array_keys(get_object_vars($this));
    }
}
