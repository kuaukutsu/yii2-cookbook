<?php

namespace cookbook\models;

use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\db\Expression;
use yii\sphinx\MatchExpression;
use yii\sphinx\Query as SphinxQuery;
use components\Gender as GenderComponent;

/**
 * Class BrandSearch
 *
 * Поиск по таблице/view Brand
 */
class BrandSearch extends Model
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var array
     */
    public $words;

    /**
     * @var int
     */
    public $gender;

    /**
     * @var int
     */
    public $categoryId;

    /**
     * @var int
     */
    public $limit = 50;

    /**
     * Id брендов, исключенных из поиска.
     *
     * @var int[]
     */
    public $excludeBrands = [];

    /**
     * Имя индекса для поиска по брэнду
     *
     * @var string
     */
    public $indexBrand = 'brand';

    /**
     * Имя индекса для поиска по каталогу
     *
     * @var string
     */
    public $indexCatalog = 'catalog_az';

    /**
     * @var GenderComponent
     */
    private $genderComponent;

    /**
     * BrandSearch constructor.
     * @param GenderComponent $genderComponent
     * @param array $config
     */
    public function __construct(GenderComponent $genderComponent, array $config = [])
    {
        $this->genderComponent = $genderComponent;

        parent::__construct($config);
    }

    /**
     * {@inheritDoc}
     */
    public function init(): void
    {
        parent::init();

        if (empty($this->indexBrand)) {
            throw new InvalidArgumentException('Index Brand not be empty');
        }

        if (empty($this->indexCatalog)) {
            throw new InvalidArgumentException('Index Catalog not be empty');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['gender', 'limit', 'categoryId'], 'integer'],
            [['title'], 'string', 'min' => 3, 'max' => 128],
            [['words'], 'each', 'rule' => ['string']],
        ];
    }

    /**
     * Ищет по индексу брэнда с проверкой наличия товаров
     *
     * @param array $params
     * @param bool $strict
     * @return array<{id: int, title: string}> Brands
     */
    public function search(array $params, bool $strict = false): array
    {
        return $this->filterConditionCatalog($this->doSearch($params, $strict));
    }

    /**
     * Ищет по индексу брэнда без проверки наличия товаров
     *
     * @param array $params
     * @param bool $strict
     * @return array<{id: int, title: string}> Brands
     */
    public function searchWithoutCheckAmount(array $params, bool $strict = false): array
    {
        return $this->doSearch($params, $strict);
    }

    /**
     * @param array $params
     * @param bool $strict
     * @return array<{id: int, title: string}> Brands
     */
    protected function doSearch(array $params, bool $strict = false): array
    {
        // проверяем что $params не пустой, иначе что искать?
        if (!$this->load($params, '') || !$this->validate()) {
            return [];
        }

        $query = (new SphinxQuery())
            ->select([
                'id',
                'title',
            ])
            ->from($this->indexBrand)
            ->where(['is_published' => true])
            ->limit($this->limit)
            ->indexBy('id');

        if (count($this->excludeBrands)) {
            $query->andWhere(['not in', 'id', $this->excludeBrands]);
        }

        if ($this->title) {
            $title = $query->getConnection()->escapeMatchValue($this->title);

            $query->match(
                (new MatchExpression())->match(['title' => $strict ? new Expression('=^'.$title.'$') : $title])
            );
        }

        if ($this->words && count($this->words) > 0) {
            $query->match(
                (new MatchExpression())
                    // ищем полное совпадение, чтобы было выше
                    ->match(['title' => $query->getConnection()->escapeMatchValue(implode(' ', $this->words))])
                    // ищем любое совпадение
                    ->orMatch(['title' => new Expression("'" . implode('|', $this->words) . "'")])
            );
        }

        return $query->all();
    }

    /**
     * Фильтруем уже полученный список brands по таблице catalog_az
     *
     * @param array<int, array{id: int, title: string}> $brands
     * @return array<int, array{id: int, title: string}>
     */
    protected function filterConditionCatalog(array $brands): array
    {
        $query = (new SphinxQuery())
            ->select(['brand_id'])
            ->from($this->indexCatalog)
            ->where(['brand_id' => array_keys($brands)])
            ->andWhere(['!=', 'amount', 0])
            ->groupBy(['brand_id']);

        if ($this->gender > 0) {
            $this->categoryId = $this->genderComponent->getGenderId($this->gender);
        }

        if ($this->categoryId > 0) {
            $query->andWhere(['category_id' => $this->categoryId]);
        }

        return array_intersect_key($brands, array_flip($query->column()));
    }
}
