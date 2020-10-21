<?php
namespace cookbook\dto;

use yii\base\Arrayable;

/**
 * Interface DtoInterface
 *
 * DTO должны реализовывать публичные методы.
 */
interface DtoInterface extends Arrayable
{
    /**
     * @param array $data
     * @return static
     */
    public static function hydrate(array $data): self;
}
