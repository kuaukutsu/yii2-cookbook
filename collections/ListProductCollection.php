<?php

namespace cookbook\collections;

use yii\base\InvalidConfigException;

/**
 * Class ListProductCollection
 *
 * @SWG\Definition(
 *     description = "Модель массива товаров из списка.",
 *     type = "array",
 *     @SWG\Items(
 *         ref = "#/definitions/ListProduct",
 *     ),
 * )
 */
class ListProductCollection extends BaseModelCollection
{
    /**
     * {@inheritDoc}
     */
    protected function getType(): string
    {
        return ListProduct::class;
    }

    /**
     * Возвращает первый элемент из коллекции.
     *
     * @return ListProduct|null
     */
    public function getFirstItem(): ?ListProduct
    {
        return $this->getIterator()[0] ?? null;
    }
}
