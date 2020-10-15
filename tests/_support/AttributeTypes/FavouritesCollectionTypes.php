<?php

namespace AttributeTypes;

/**
 * Class FavouritesCollectionTypes
 */
class FavouritesCollectionTypes extends FavouritesItemTypes
{
    /**
     * {@inheritDoc}
     */
    public function assertAttributeTypes(array $data): void
    {
        // берём первый элемент коллекции
        parent::assertAttributeTypes(array_shift($data));
    }
}
