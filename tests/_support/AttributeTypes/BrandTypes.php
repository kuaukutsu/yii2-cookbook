<?php

namespace AttributeTypes;

use app\api\v2\models\Brand;
use app\models\Image;
use yii\base\Model;

/**
 * Class BrandTypes
 */
class BrandTypes extends BaseAssert
{
    /**
     * {@inheritDoc}
     */
    protected function model(): Model
    {
        return new Brand();
    }

    /**
     * {@inheritDoc}
     */
    protected function depends(): array
    {
        return [
            Image::class => new ImageTypes($this->tester),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function exclude(): array
    {
        return [
            'logo' => self::TYPE_NULL,
            'image' => self::TYPE_NULL,
            'description' => self::TYPE_NULL,
        ];
    }
}
