<?php

namespace AttributeTypes;

use AcceptanceTester;
use app\components\validators\InstanceofValidator;
use app\components\validators\ToStringValidator;
use yii\base\Model;
use yii\validators\BooleanValidator;
use yii\validators\EachValidator;
use yii\validators\NumberValidator;
use yii\validators\StringValidator;

/**
 * Class BaseAssert
 */
abstract class BaseAssert
{
    protected const TYPE_INTEGER = 'integer';
    protected const TYPE_FLOAT = 'double'; // @see https://www.php.net/manual/en/language.types.float.php
    protected const TYPE_BOOLEAN = 'boolean';
    protected const TYPE_STRING = 'string';
    protected const TYPE_ARRAY = 'array';
    protected const TYPE_NULL = 'NULL';

    /**
     * @var AcceptanceTester;
     */
    protected $tester;

    /**
     * BaseSnapshot constructor.
     * @param AcceptanceTester $I
     */
    public function __construct(AcceptanceTester $I)
    {
        $this->tester = $I;
    }

    /**
     * Ссылка на объект модели, откуда будут браться правила (`rules`) для анализа типов данных.
     *
     * @return Model
     */
    abstract protected function model(): Model;

    /**
     * Зависимости. Список, массив, где:
     * - в качестве ключа имя класса модели `Model::class`
     * - а в качестве значения ссылка на экземпляр класса описания `new ModelTypes($this->tester)`
     *
     * @return array<string, object>
     */
    protected function depends(): array
    {
        return [];
    }

    /**
     * Исключения. Список, массив, где:
    - в качестве ключа имя аттрибута
    - а в качестве значения возможный тип `gettype(null)`
     *
     * @return array<string, string>
     */
    protected function exclude(): array
    {
        return [];
    }

    /**
     * @param array $data
     */
    public function assertAttributeTypes(array $data): void
    {
        $attr = $this->detectAttributeTypes($this->model());
        foreach ($data as $key => $value) {
            if (isset($attr[$key])) {
                if ($attr[$key] instanceof self) {
                    if ($value) {
                        $attr[$key]->assertAttributeTypes($value);
                        continue;
                    }

                    /**
                     * Если значение "пустое", то проверим, это должен быть массив
                     * Иначе, возможно что ошибка (либо необходимо добавить в exclude).
                     */
                    $attr[$key] = self::TYPE_ARRAY;
                }

                $this->contains($key, $attr[$key], gettype($value));
            }
        }
    }

    /**
     * Composes default value for [[attributeTypes]] from the owner validation rules.
     * @param Model $model
     * @return array<string, mixed> attribute type map.
     */
    protected function detectAttributeTypes(Model $model): array
    {
        $attributeTypes = [];
        foreach ($model->getValidators() as $validator) {
            $type = null;
            if ($validator instanceof BooleanValidator) {
                $type = self::TYPE_BOOLEAN;
            } elseif ($validator instanceof NumberValidator) {
                $type = $validator->integerOnly ? self::TYPE_INTEGER : self::TYPE_FLOAT;
            } elseif ($validator instanceof StringValidator) {
                $type = self::TYPE_STRING;
            } elseif ($validator instanceof ToStringValidator) {
                $type = self::TYPE_STRING;
            } elseif ($validator instanceof EachValidator) {
                $type = self::TYPE_ARRAY;
            } elseif ($validator instanceof InstanceofValidator) {
                $type = $this->depends()[$validator->className] ?? null;
                if ($type === null && $validator->isCollection()) {
                    $type = self::TYPE_ARRAY;
                }
            }

            if ($type !== null) {
                foreach ((array) $validator->attributes as $attribute) {
                    $attributeTypes[ltrim($attribute, '!')] = $type;
                }
            }
        }

        return $attributeTypes;
    }

    /**
     * @param string $property
     * @param string $needle
     * @param string $haystack
     */
    protected function contains(string $property, string $needle, string $haystack): void
    {
        $exclude = $this->exclude();
        if (isset($exclude[$property]) && $exclude[$property] === $haystack) {
            return;
        }

        $this->tester->assertContains($needle, $haystack, 'Field "'.$property.'"');
    }
}
