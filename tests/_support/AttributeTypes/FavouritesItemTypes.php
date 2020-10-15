<?php

namespace AttributeTypes;

use app\api\v2\models\ListProduct;
use app\api\v2\models\lists\FavouritesItem;
use yii\base\Model;

/**
 * Class FavouritesItemTypes
 */
class FavouritesItemTypes extends BaseAssert
{
    /**
     * {@inheritDoc}
     */
    protected function model(): Model
    {
        return new FavouritesItem();
    }

    /**
     * {@inheritDoc}
     */
    protected function depends(): array
    {
        return [
            ListProduct::class => new ListProductTypes($this->tester),
        ];
    }
}
