<?php

namespace cookbook\models;

use yii\base\InvalidConfigException;
use app\api\v2\models\ListProduct;
use app\api\v2\models\lists\FavouritesItem;
use app\api\v2\components\products\FavouritesService;

/**
 * Class FavouritesForm
 */
class FavouritesForm extends BaseForm
{
    /**
     * @var int
     */
    public $productId;

    /**
     * @var FavouritesService
     */
    private $service;

    /**
     * FavouritesForm constructor.
     *
     * @param FavouritesService $service
     * @param array $config
     */
    public function __construct(FavouritesService $service, $config = [])
    {
        $this->service = $service;

        parent::__construct($config);
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['productId'], 'required'],
            [['productId'], 'integer'],
            [['productId'], 'validateProductExists'],
        ];
    }

    /**
     * Проверяем что указанный ID существует.
     *
     * @param string $attribute
     * @throws InvalidConfigException
     */
    public function validateProductExists(string $attribute): void
    {
        if ($this->service->getProduct($this->$attribute)) {
            return;
        }

        $this->addError($attribute, 'Некорректный ID товарной характеристики.');
    }

    /**
     * Добавить переданное значение в список.
     *
     * @return bool
     */
    public function add(): bool
    {
        if ($this->validate()) {
            $this->service->add($this->productId);
            return true;
        }

        return false;
    }

    /**
     * Удалить переданное значение из списка.
     *
     * @return bool
     */
    public function remove(): bool
    {
        if ($this->validate()) {
            $this->service->remove($this->productId);
            return true;
        }

        return false;
    }
}
