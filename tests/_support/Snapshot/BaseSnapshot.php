<?php

namespace Snapshot;

use AcceptanceTester;
use app\helpers\ArrayHelper;
use Codeception\Exception\ContentNotFound;
use Codeception\Snapshot;
use Exception;

/**
 * Class BaseSnapshot
 */
abstract class BaseSnapshot extends Snapshot
{
    /**
     * @var AcceptanceTester;
     */
    protected $tester;

    /**
     * @var array
     */
    private $unsetAttribute = [];

    /**
     * @var array
     */
    private $excludeAttributes = [];

    /**
     * @var bool
     */
    private $isFirstItem = false;

    /**
     * BaseSnapshot constructor.
     * @param AcceptanceTester $I
     */
    public function __construct(AcceptanceTester $I)
    {
        $this->tester = $I;

        /**
         * Если необходимо обновить snapshot.
         * Параметры задаются через конфигурацию tests/acceptance.suite.yml
         */
        if ($this->tester->isReloadSnapshot()) {
            $this->shouldRefreshSnapshot();
        }
    }

    /**
     * @return array
     */
    public function getUnsetAttribute(): array
    {
        return $this->unsetAttribute;
    }

    /**
     * @param array $unsetAttribute
     */
    public function setUnsetAttribute(array $unsetAttribute): void
    {
        $this->unsetAttribute = $unsetAttribute;
    }

    /**
     * @return array
     */
    public function getExcludeAttributes(): array
    {
        return $this->excludeAttributes;
    }

    /**
     * @param array $excludeAttributes
     */
    public function setExcludeAttributes(array $excludeAttributes): void
    {
        $this->excludeAttributes = $excludeAttributes;
    }

    /**
     * Performs assertion for data sets
     * Обёртка над assert, для того чтобы делать проверку толко по одному, первому элементу.
     * Для проверки операций tryIndex, где списки могут отличаться по кол-во элементов на разных серверах.
     */
    public function assertFirstItem(): void
    {
        $this->isFirstItem = true;
        $this->assert();
    }

    /**
     * {@inheritDoc}
     */
    public function assert(): void
    {
        if ($this->tester->isEnableSnapshot()) {
            parent::assert();
        }
    }

    /**
     * Данный метод описывает как и какие данные должны быть получены и сохранены для snapshot.
     *
     * @return array
     * @throws Exception
     */
    protected function fetchData(): array
    {
        return $this->tester->grabDataFromResponseByJsonPath('$.');
    }

    /**
     * If no filename is defined, generates one from class name
     *
     * @return string
     */
    protected function getFileName(): string
    {
        if (!$this->fileName) {
            $methodName = get_class($this) . '.' . $this->tester->getActionName();
            $this->fileName = preg_replace('/\W/', '.', $methodName). '.json';
        }
        return codecept_data_dir() . $this->fileName;
    }

    /**
     * Performs assertion on saved data set against current dataset.
     * Can be overridden to implement custom assertion
     *
     * @param $data
     */
    protected function assertData($data): void
    {
        if (is_array($this->dataSet)) {
            $data[0] = $this->doUnsetAttributes(
                $this->isFirstItem
                    ? array_shift($data[0]) ?? []
                    : $data[0]
            );

            if (is_array($this->dataSet[0]) && is_array($data[0])) {
                $diff = ArrayHelper::diffStructure($this->dataSet[0], $data[0], $this->tester->isStrictResponse());
                $this->assertCount(0, $diff, 'Ответы не совпадают.');
                return;
            }

            $this->assertContains($this->dataSet[0], $data[0]);
            return;
        }

        parent::assertData($data);
    }

    /**
     * Loads data set from file.
     */
    protected function load(): void
    {
        if (!file_exists($this->getFileName())) {
            return;
        }

        /**
         * Нам нужны именно ассоциативные массивы, так проще сравнивать.
         */
        $this->dataSet = json_decode(file_get_contents($this->getFileName()), true);
        if (!$this->dataSet) {
            throw new ContentNotFound('Loaded snapshot is empty');
        }
    }

    /**
     * Saves data set to file
     */
    protected function save(): void
    {
        if ($this->isFirstItem) {
            $this->dataSet[0] = array_shift($this->dataSet[0]);
        }

        if (count($this->getUnsetAttribute()) || count($this->getExcludeAttributes())) {
            $this->dataSet[0] = $this->doUnsetAttributes($this->dataSet[0]);
        }

        file_put_contents($this->getFileName(), json_encode($this->dataSet));
    }

    /**
     * Удаляем значения для заданных атрибутов, например для уникальных, автоинкрементных даных.
     * Задаётся перед проверкой, например:
     *
     * ```php
     * $snapshot->setUnsetAttribute(['$.id', '$.phones.[0-9]+.id']);
     * ```
     *
     * ```php
     * $snapshot->setUnsetAttribute(['$.*.id']);
     * ```
     *
     * @param array $data
     * @param string|null $path
     * @return array
     */
    protected function doUnsetAttributes(array $data, string $path = null): array
    {
        $result = [];
        $path = $path ?? '$';

        if ($this->existAttributes($path, $this->getExcludeAttributes())) {
            return [];
        }

        foreach ($data as $key => $value) {
            if ($this->existAttributes($path . '.' . $key, $this->getExcludeAttributes())) {
                continue;
            }

            if ($this->existAttributes($path . '.' . $key, $this->getUnsetAttribute())) {
                $result[$key] = null;
                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->doUnsetAttributes($value, $path . '.' . $key);
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * проверяем, есть ли соответствие переданного пути и текущего ключа,
     * используется из метода doUnsetAttributes()
     *
     * @param string $path
     * @param array $attributes
     * @return bool
     */
    protected function existAttributes(string $path, array $attributes): bool
    {
        if ($attributes === []) {
            return false;
        }

        if (in_array($path, $attributes, true)) {
            return true;
        }

        foreach ($attributes as $jsonpath) {
            $jsonpath = '\\' . $jsonpath;
            if (preg_match('#^' . $jsonpath . '$#', $path, $match)) {
                return true;
            }
        }

        return false;
    }
}
