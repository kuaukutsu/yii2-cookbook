<?php

namespace cookbook\models;

use components\validators\InstanceofValidator;
use dto\OrderProductDto;
use yii\base\InvalidConfigException;
use yii\base\Model;

/**
 * @SWG\Definition(
 *    description = "Модель товара в заказе.",
 * )
 *
 */
class OrderItem extends Model
{
    /**
     * Количество заказанного товара
     *
     * @SWG\Property()
     *
     * @var int
     */
    public $quantity;

    /**
     * Товаров в заказе
     *
     * @SWG\Property()
     *
     * @var ListProduct
     */
    private $product;

    /**
     * Модель размера
     *
     * @SWG\Property()
     *
     * @var StockItemSize
     */
    private $size;

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['quantity'], 'integer'],
            [['product'], InstanceofValidator::class, 'className' => ListProduct::class],
            [['size'], InstanceofValidator::class, 'className' => StockItemSize::class],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function fields(): array
    {
        return [
            'quantity',
            'product',
            'size',
        ];
    }

    /**
     * @param OrderProductDto[] $listDto
     * @return OrderItemCollection
     * @throws InvalidConfigException
     */
    public static function loadFromDto(array $listDto): OrderItemCollection
    {
        $itemsProduct = ListProduct::loadFromApi(array_column($listDto, 'id'));

        $collection = new OrderItemCollection();
        foreach ($listDto as $dto) {
            $collection->attach(
                new self([
                    'quantity' => $dto->getQuantity(),
                    'product' => $itemsProduct[$dto->getId()] ?? null,
                    'size' => StockItemSize::loadFromDto($dto->getSize()),
                ])
            );
        }

        return $collection;
    }

    /**
     * @param ListProduct|null $listProduct
     */
    public function setProduct(?ListProduct $listProduct): void
    {
        $this->product = $listProduct;
    }

    /**
     * @return ListProduct|null
     */
    public function getProduct(): ?ListProduct
    {
        return $this->product;
    }

    /**
     * @param StockItemSize $stockItemSize
     */
    public function setSize(StockItemSize $stockItemSize): void
    {
        $this->size = $stockItemSize;
    }

    /**
     * @return StockItemSize|null
     */
    public function getSize(): ?StockItemSize
    {
        return $this->size;
    }
}
