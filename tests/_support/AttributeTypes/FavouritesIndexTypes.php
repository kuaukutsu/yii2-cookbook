<?php

namespace AttributeTypes;

use app\api\v2\models\lists\FavouritesCollection;
use app\api\v2\models\lists\FavouritesIndex;
use app\models\Pagination;
use yii\base\Model;

/**
 * Class FavouritesIndexTypes
 */
class FavouritesIndexTypes extends BaseAssert
{
    /**
     * {@inheritDoc}
     */
    protected function model(): Model
    {
        return new FavouritesIndex();
    }

    /**
     * {@inheritDoc}
     */
    protected function depends(): array
    {
        return [
            FavouritesCollection::class => new FavouritesCollectionTypes($this->tester),
            Pagination::class => new PaginationTypes($this->tester),
        ];
    }
}
