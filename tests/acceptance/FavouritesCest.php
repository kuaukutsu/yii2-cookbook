<?php

use AttributeTypes\FavouritesIndexTypes;
use AttributeTypes\FavouritesCollectionTypes;
use Codeception\Example;
use Snapshot\Favourites;
use tests\_support\HelperTrait;

/**
 * Class FavouritesCest
 * @see \app\api\v2\controllers\FavouritesController
 */
class FavouritesCest
{
    use HelperTrait;

    private const PRODUCT_ID = 168760;
    private const PRODUCT_SECOND_ID = 115888;

    /**
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/favourites/", 200]
     * @group compare-versions
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Favourites $snapshot
     */
    public function tryCreate169056(AcceptanceTester $I, Example $example, Favourites $snapshot): void
    {
        $I->sendPOST($example[0], [
            'productId' => self::PRODUCT_ID,
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // compare
        $snapshot->setExcludeAttributes([
            '$.product.inWishlist',
            '$.product.photos.([1-9]|[0-9]{2,})',
            '$.product.chartTypes.([1-9]|[0-9]{2,})',
        ]);
        $snapshot->assert();
    }

    /**
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/favourites/", 400]
     * @group base
     * @group exception
     *
     * @param AcceptanceTester $I
     * @param Example $example
     */
    public function tryCreateFailNotProductId(AcceptanceTester $I, Example $example): void
    {
        $I->sendPOST($example[0], [
            'productId' => 'test'
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();
    }

    /**
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/favourites/", 400]
     * @group base
     * @group exception
     *
     * @param AcceptanceTester $I
     * @param Example $example
     */
    public function tryCreateFailBadProductId(AcceptanceTester $I, Example $example): void
    {
        $I->sendPOST($example[0], []);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();
    }

    /**
     * @depends tryCreate169056
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/favourites/", 200]
     * @group compare-versions
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Favourites $snapshot
     * @param FavouritesIndexTypes $types
     * @throws Exception
     */
    public function tryIndex(AcceptanceTester $I, Example $example, Favourites $snapshot, FavouritesIndexTypes $types): void
    {
        $I->sendGET($example[0]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // assert types
        $I->assertAttributeTypes($types);

        // compare
        $snapshot->setExcludeAttributes([
            '$.items.*.product.inWishlist',
            '$.items.*.product.photos.([1-9]|[0-9]{2,})',
            '$.items.*.product.chartTypes.([1-9]|[0-9]{2,})',
        ]);
        $snapshot->assert();
    }

    /**
     * @depends tryCreate169056
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/favourites/ids-list/", 200]
     * @group compare-versions
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Favourites $snapshot
     */
    public function tryListIds(AcceptanceTester $I, Example $example, Favourites $snapshot): void
    {
        $I->sendGET($example[0]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // compare
        $snapshot->assert();
    }

    /**
     * @depends tryCreate169056
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/favourites/get-by-ids/", 200]
     * @group compare-versions
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Favourites $snapshot
     * @param FavouritesCollectionTypes $types
     * @throws Exception
     */
    public function tryGetByIds169056(
        AcceptanceTester $I,
        Example $example,
        Favourites $snapshot,
        FavouritesCollectionTypes $types
    ): void {
        $I->sendGET($example[0], [
            'ids' => [
                self::PRODUCT_ID,
            ]
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // assert types
        $I->assertAttributeTypes($types);

        // compare
        $snapshot->setExcludeAttributes([
            '$.product.inWishlist',
            '$.product.photos.([1-9]|[0-9]{2,})',
            '$.product.chartTypes.([1-9]|[0-9]{2,})',
        ]);
        $snapshot->assertFirstItem();
    }

    /**
     * @depends tryCreate169056
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/favourites/", 200]
     * @group compare-versions
     *
     * @param AcceptanceTester $I
     * @param Example $example
     * @param Favourites $snapshot
     */
    public function tryDelete169056(AcceptanceTester $I, Example $example, Favourites $snapshot): void
    {
        $I->sendDELETE($example[0], [
            'productId' => self::PRODUCT_ID,
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // compare
        $snapshot->setExcludeAttributes([
            '$.product.inWishlist',
            '$.product.photos.([1-9]|[0-9]{2,})',
            '$.product.chartTypes.([1-9]|[0-9]{2,})',
        ]);
        $snapshot->assert();
    }

    /**
     * @before login
     * @before setHeaderRequired
     * @example ["/favourites/", 200]
     * @group base
     *
     * @param AcceptanceTester $I
     * @param Example $example
     */
    public function tryUserCreate169056(AcceptanceTester $I, Example $example): void
    {
        $I->sendPOST($example[0], [
            'productId' => self::PRODUCT_ID,
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // check product
        $I->seeResponseContainsJson(['product' => ['id' => self::PRODUCT_ID]]);
    }

    /**
     * @depends tryUserCreate169056
     * @before login
     * @before setHeaderRequired
     * @example ["/favourites/get-by-ids/", 200]
     * @group base
     *
     * @param AcceptanceTester $I
     * @param Example $example
     */
    public function tryUserGetByIds169056(AcceptanceTester $I, Example $example): void
    {
        $I->sendGET($example[0], [
            'ids' => [
                self::PRODUCT_ID,
            ]
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // check product
        $I->seeResponseContainsJson(['product' => ['id' => self::PRODUCT_ID]]);
    }

    /**
     * @depends tryUserCreate169056
     * @before login
     * @before setHeaderRequired
     * @example ["/favourites/", 200]
     * @group base
     *
     * @param AcceptanceTester $I
     * @param Example $example
     */
    public function tryUserDelete169056(AcceptanceTester $I, Example $example): void
    {
        $I->sendDELETE($example[0], [
            'productId' => self::PRODUCT_ID,
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // check product
        $I->seeResponseContainsJson(['product' => ['id' => self::PRODUCT_ID]]);
    }

    /**
     * @depends tryUserCreate169056
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @example ["/favourites/", 200]
     * @group base
     *
     * @param AcceptanceTester $I
     * @param Example $example
     */
    public function tryMigrateSession(AcceptanceTester $I, Example $example): void
    {
        $I->sendPOST($example[0], [
            'productId' => self::PRODUCT_ID
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // get list
        $I->amHeaderAuthenticated();
        $I->sendGET($example[0]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // check product
        $I->seeResponseContainsJson(['items' => ['product' => ['id' => self::PRODUCT_ID]]]);

        $I->sendDELETE($example[0], [
            'productId' => self::PRODUCT_ID,
        ]);
    }

    /**
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @param AcceptanceTester $I
     * @param Example $example
     *
     * @example ["/favourites/", 200]
     * @group base
     *
     */
    public function tryCreateSeveralItems(AcceptanceTester $I, Example $example): void
    {
        $I->sendPOST($example[0], [
            'productId' => self::PRODUCT_ID
        ]);

        $I->sendPOST($example[0], [
            'productId' => self::PRODUCT_SECOND_ID
        ]);
    }

    /**
     * @depends tryCreateSeveralItems
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @param AcceptanceTester $I
     * @param Example $example
     * @throws Exception
     *
     * @example ["/favourites/", 200]
     * @group base
     *
     */
    public function tryListReversOrder(AcceptanceTester $I, Example $example): void
    {
        $I->sendGET($example[0]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // проверяем что последний добавленный элемент - первый в списке
        $response = $I->grabDataFromResponseByJsonPath('$.items.0')[0] ?? [];
        $I->assertArrayHasKey('product', $response);
        $I->assertEquals(self::PRODUCT_SECOND_ID, $response['product']['id'] ?? null);
    }

    /**
     * @depends tryCreateSeveralItems
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @param AcceptanceTester $I
     * @param Example $example
     * @throws Exception
     *
     * @example ["/favourites/ids-list/", 200]
     * @group base
     *
     */
    public function tryListIdsReversOrder(AcceptanceTester $I, Example $example): void
    {
        $I->sendGET($example[0]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();

        // проверяем что последний добавленный элемент - первый в списке
        $response = $I->grabDataFromResponseByJsonPath('$.')[0] ?? [];
        $I->assertEquals(self::PRODUCT_SECOND_ID, $response[0] ?? null);
    }

    /**
     * @depends tryCreateSeveralItems
     * @before setCookiePhpSessid
     * @before setHeaderRequired
     * @param AcceptanceTester $I
     * @param Example $example
     * @example ["/favourites/", 200]
     * @group base
     *
     */
    public function tryUserDeleteSeveralItems(AcceptanceTester $I, Example $example): void
    {
        $I->sendDELETE($example[0], [
            'productId' => self::PRODUCT_ID
        ]);

        $I->sendDELETE($example[0], [
            'productId' => self::PRODUCT_SECOND_ID
        ]);

        // check response
        $I->seeResponseCodeIs($example[1]);
        $I->seeResponseIsJson();
    }
}
