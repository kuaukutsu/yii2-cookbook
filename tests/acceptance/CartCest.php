<?php

use AttributeTypes\CartItemTypes;
use AttributeTypes\CartItemCollectionTypes;
use AttributeTypes\CartInfoTypes;
use AttributeTypes\TextBlockTypes;
use Codeception\Example;
use Page\Acceptance\Product;
use Snapshot\Cart;
use tests\_support\HelperTrait;

/**
 * Class CartCest
 * @see \app\api\v2\controllers\CartController
 */
class CartCest
{
    use HelperTrait;

    private const DISCOUNT_CODE = 'test30p';

    /**
     * Stack для хранения данных
     *
     * @var array
     */
    private $stack = [
        'stockItemId' => null,
        'stockItemIdSecond' => null,
    ];

    /**
     * Чистим корзину, возможно там что-то осталось от предыдущих тестов.
     *
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/cart/clear/", 200]
     * @group compare-versions
     *
     * @param AcceptanceTester $I
     * @param Example $example
     */
    public function tryClear(AcceptanceTester $I, Example $example): void
    {
        $I->sendPOST($example[0]);
    }

    /**
     * Добавляем в корзину товар. Работаем из под Гость.
     *
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/cart/", 200]
     * @group compare-versions
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Product $product
     * @param Cart $snapshot
     * @param CartItemTypes $types
     * @throws Exception
     */
    public function tryCreate(AcceptanceTester $I, Example $example, Product $product, Cart $snapshot, CartItemTypes $types): void
    {
        $this->stack['stockItemId'] = $product->getItemFirstStockIds()[0];

        // request
        $I->sendPOST($example[0], [
            'stockItemId' => $this->stack['stockItemId'],
            'quantity' => 1
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // assert types
        $I->assertAttributeTypes($types);

        // compare
        $snapshot->setExcludeAttributes([
            '$.inWishlist',
            '$.product.inWishlist',
            '$.product.photos.([1-9]|[0-9]{2,})',
            '$.product.chartTypes.([1-9]|[0-9]{2,})'
        ]);
        $snapshot->assert();
    }

    /**
     * Проверяем ошибку, если попробуем положить слишком много товаров.
     *
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/cart/", 400]
     * @group base
     * @group exception
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Product $product
     */
    public function tryCreateNotEnoughProduct(AcceptanceTester $I, Example $example, Product $product): void
    {
        $I->sendPOST($example[0], [
            'stockItemId' => $product->getItemFirstStockIds()[0],
            'quantity' => 1000
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // check message
        $I->seeResponseContainsJson(['message' => 'Недостаточно товара.']);
    }

    /**
     * Смотрим что лежит в корзине
     *
     * @depends tryCreate
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/cart/", 200]
     * @group compare-versions
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Cart $snapshot
     * @param CartItemCollectionTypes $types
     * @throws Exception
     */
    public function tryIndex(AcceptanceTester $I, Example $example, Cart $snapshot, CartItemCollectionTypes $types): void
    {
        $I->sendGET($example[0]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // assert types
        $I->assertAttributeTypes($types);

        // compare
        $snapshot->setExcludeAttributes([
            '$.inWishlist',
            '$.product.inWishlist',
            '$.product.photos.([1-9]|[0-9]{2,})',
            '$.product.chartTypes.([1-9]|[0-9]{2,})'
        ]);
        $snapshot->assertFirstItem();

        // проверяем что верное кол-во и товар.
        $I->seeResponseContainsJson(['stockItemId' => $this->stack['stockItemId']]);
        $I->seeResponseContainsJson(['quantity' => 1]);
    }

    /**
     * @depends tryCreate
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/cart/split/", 200]
     * @group compare-versions
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Cart $snapshot
     * @param CartInfoTypes $types
     * @throws Exception
     */
    public function trySplit(AcceptanceTester $I, Example $example, Cart $snapshot, CartInfoTypes $types): void
    {
        $I->sendGET($example[0], [
            'discount' => self::DISCOUNT_CODE,
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // assert types
        $I->assertAttributeTypes($types);

        // compare
        $snapshot->setExcludeAttributes([
            '$.summary.([1-9]|[0-9]{2,})',
            '$.groups.([1-9]|[0-9]{2,})',
            '$.groups.0.items.([1-9]|[0-9]{2,})',
            '$.groups.0.items.0.inWishlist',
            '$.groups.0.items.0.product.inWishlist',
            '$.groups.0.items.0.product.photos.([1-9]|[0-9]{2,})',
            '$.groups.0.items.0.product.chartTypes.([1-9]|[0-9]{2,})'
        ]);
        $snapshot->assert();

        // проверяем что верное кол-во и товар.
        $I->seeResponseContainsJson(['stockItemId' => $this->stack['stockItemId']]);
        $I->seeResponseContainsJson(['quantity' => 1]);

        // assert response discount
        $I->seeResponseContainsJson(['title' => 'СКИДКА ПО ПРОМОКОДУ']);
    }

    /**
     * @depends tryCreate
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/cart/text-info/", 200]
     * @group compare-versions
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param TextBlockTypes $types
     * @throws Exception
     */
    public function tryTextInfo(AcceptanceTester $I, Example $example, TextBlockTypes $types): void
    {
        $I->sendGET($example[0]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // assert types
        $types->assertAttributeTypes($I->grabDataFromResponseByJsonPath('$.0')[0] ?? []);
    }

    /**
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/cart/", 400]
     * @group base
     * @group exception
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Product $product
     */
    public function tryUpdateNotEnoughProduct(AcceptanceTester $I, Example $example, Product $product): void
    {
        $I->sendPUT($example[0], [
            'stockItemId' => $product->getItemFirstStockIds()[0],
            'newStockItemId' => 1,
            'quantity' => 2
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // check message
        $I->seeResponseContainsJson(['message' => 'stockItemId и newStockItemId относятся к разным товарам.']);
    }

    /**
     * @depends tryCreate
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/cart/", 200]
     * @group compare-versions
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Cart $snapshot
     */
    public function tryDelete(AcceptanceTester $I, Example $example, Cart $snapshot): void
    {
        $I->sendDELETE($example[0], [
            'stockItemId' => $this->stack['stockItemId'],
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // compare
        $snapshot->setExcludeAttributes([
            '$.inWishlist',
            '$.product.inWishlist',
            '$.product.photos.([1-9]|[0-9]{2,})',
            '$.product.chartTypes.([1-9]|[0-9]{2,})'
        ]);
        $snapshot->assert();
    }

    /**
     * @before login
     * @before setHeaderRequired
     * @example ["/cart/clear/", 200]
     * @group compare-versions
     *
     * @param AcceptanceTester $I
     * @param Example $example
     */
    public function tryUserClear(AcceptanceTester $I, Example $example): void
    {
        $I->sendPOST($example[0]);
    }

    /**
     * @before login
     * @before setHeaderRequired
     * @example ["/cart/", 200]
     * @group base
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Product $product
     */
    public function tryUserCreate(AcceptanceTester $I, Example $example, Product $product): void
    {
        $this->doUserCreate($this->stack['stockItemId'] = $product->getItemFirstStockIds()[0], $I, $example);
        $this->doUserCreate($this->stack['stockItemIdSecond'] = $product->getItemSecondStockIds()[0], $I, $example);
    }

    /**
     * @param int $id
     * @param AcceptanceTester $I
     * @param Example $example
     */
    private function doUserCreate(int $id, AcceptanceTester $I, Example $example): void
    {
        $I->sendPOST($example[0], [
            'stockItemId' => $id,
            'quantity' => 1,
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // check stockItemId
        $I->seeResponseContainsJson(['stockItemId' => $id]);
    }

    /**
     * @depends tryUserCreate
     * @before login
     * @before setHeaderRequired
     * @example ["/cart/", 200]
     * @group base
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param CartItemTypes $types
     * @throws Exception
     */
    public function tryUserUpdate(AcceptanceTester $I, Example $example, CartItemTypes $types): void
    {
        $I->sendPUT($example[0], [
            'stockItemId' => $this->stack['stockItemId'],
            'quantity' => 2,
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // assert types
        $I->assertAttributeTypes($types);

        // check stockItemId
        $I->seeResponseContainsJson(['stockItemId' => $this->stack['stockItemId']]);
        $I->seeResponseContainsJson(['quantity' => 2]);
    }

    /**
     * @depends tryUserCreate
     * @before login
     * @before setHeaderRequired
     * @example ["/cart/", 200]
     * @group base
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Product $product
     */
    public function tryUserChangeGoods(AcceptanceTester $I, Example $example, Product $product): void
    {
        // меняем размер первого товара в корзине
        $I->sendPUT($example[0], [
            'stockItemId' => $product->getItemFirstStockIds()[0],
            'newStockItemId' => $this->stack['stockItemId'] = $product->getItemFirstStockIds()[1],
            'quantity' => 2,
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // check stockItemId
        $I->seeResponseContainsJson(['stockItemId' => $this->stack['stockItemId']]);
        $I->seeResponseContainsJson(['quantity' => 2]);
    }

    /**
     * @depends tryUserCreate
     * @before login
     * @before setHeaderRequired
     * @example ["/cart/", 200]
     * @group base
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @throws Exception
     */
    public function tryUserIndex(AcceptanceTester $I, Example $example): void
    {
        $I->sendGET($example[0]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // count: проверяем что в корзине 2 товара @see tryUserCreate
        $response = $I->grabDataFromResponseByJsonPath('$.')[0] ?? [];
        $I->assertCount(2, $response);
    }

    /**
     * @depends tryUserCreate
     * @before login
     * @before setHeaderRequired
     * @example ["/cart/split/", 200]
     * @group base
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @throws Exception
     */
    public function tryUserSplitWithDiscount(AcceptanceTester $I, Example $example): void
    {
        $I->sendGET($example[0], [
            'discount' => self::DISCOUNT_CODE,
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // count: проверяем что в группе 2 товара @see tryUserCreate
        $response = $I->grabDataFromResponseByJsonPath('$.groups.0')[0] ?? [];
        $I->assertCount(2, $response['items'] ?? []);

        // assert response discount
        $I->seeResponseContainsJson(['title' => 'СКИДКА ПО ПРОМОКОДУ']);
    }

    /**
     * @depends tryUserCreate
     * @before login
     * @before setHeaderRequired
     * @example ["/cart/", 200]
     * @group base
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param CartItemTypes $types
     * @throws Exception
     */
    public function tryUserDelete(AcceptanceTester $I, Example $example, CartItemTypes $types): void
    {
        $this->doUserDelete($this->stack['stockItemId'], $I, $example, $types);
        $this->doUserDelete($this->stack['stockItemIdSecond'], $I, $example, $types);
    }

    /**
     * @param int $id
     * @param AcceptanceTester $I
     * @param Example $example
     * @param CartItemTypes $types
     */
    private function doUserDelete(int $id, AcceptanceTester $I, Example $example, CartItemTypes $types): void
    {
        $I->sendDELETE($example[0], [
            'stockItemId' => $id,
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // assert types
        $I->assertAttributeTypes($types);

        // check stockItemId
        $I->seeResponseContainsJson(['stockItemId' => $id]);
    }

    /**
     * Сценарий когда в корзину кладёт гость, и после регистрации, авторизации товар мигрирует в корзину пользователя.
     *
     * @depends tryCreate
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/cart/", 200]
     * @group base
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Product $product
     */
    public function tryMigrateGuestToUser(AcceptanceTester $I, Example $example, Product $product): void
    {
        $this->logout($I);

        // получаем stockItemId
        $id = $product->getItemFirstStockIds()[0];

        // положить в корзину
        $I->sendPOST($example[0], [
            'stockItemId' => $id,
            'quantity' => 1
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // check stockItemId
        $I->seeResponseContainsJson(['product' => ['id' => $product->getItemFirstId()]]);

        $this->login($I);

        // посмотреть что в корзине
        $I->sendGET($example[0]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // check stockItemId
        $I->seeResponseContainsJson(['product' => ['id' => $product->getItemFirstId()]]);

        // удалить из корзины
        $I->sendDELETE($example[0], [
            'stockItemId' => $id,
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();
    }
}
