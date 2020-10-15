<?php

namespace cookbook\behaviors;

use Yii;
use yii\base\ActionFilter;
use yii\base\Component;
use yii\web\Request;

/**
 * Class RequestBehavior
 *
 * Обрабатываем данные от GET/POST.
 * Область применения: например UTM метки
 *
 * example:
 * [
 *  'header' => [
 *      'class' => self,
 *      'rules' => [
 *          [
 *              'class' => RequestBehavior,
 *              'enable' => true,
 *              'attributes' => [
 *                  'attr',
 *                  'test' => 'test',
 *                  'testCall' => function (array $params): ?mixed,
 *              ]
 *          ],
 *          [
 *              HeaderRuleInterface::class
 *          ],
 *      ]
 *  ]
 * ]
 *
 * @property Component $owner
 */
class RequestBehavior extends ActionFilter
{
    protected const PARAMS_ON_GET = 1 << 0;
    protected const PARAMS_ON_POST = 1 << 1;

    /**
     * Правила которые должны быть выполнены.
     *
     * example:
     *
     * [
     *  'attrCall' => static function (array $params): ?mixed, если null|false|0 то не будет вызвано присваивание
     *  'attrString' => 'paramsNameFromGET/POST', будет вызван сеттер paramsNameFromGET -> setParamsNameFromGET()
     * ]
     *
     * @var array[][]
     */
    public $attributes = [];

    /**
     * Возможность отключить фильтр.
     *
     * @var bool
     */
    public $enable = true;

    /**
     * @var Request
     */
    public $request;

    /**
     * {@inheritDoc}
     */
    public function init(): void
    {
        parent::init();

        if ($this->request === null) {
            $this->request = Yii::$app->getRequest();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function beforeAction($action): bool
    {
        if (parent::beforeAction($action)) {
            if ($this->enable) {
                $params = $this->getParams();
                foreach ($this->attributes as $attribute => $rule) {
                    /**
                     * в attribute может быть простой и ассоциативный массив,
                     * в случае если простой массив (по факту это значит что attribute есть rule), то:
                     */
                    if (is_int($attribute)) {
                        $attribute = $rule;
                    }

                    if (!$this->owner->hasProperty($attribute)) {
                        continue;
                    }

                    if (is_callable($rule) && $result = $rule($params)) {
                        $this->owner->{$attribute} = $result;
                        continue;
                    }

                    if (isset($params[$attribute])) {
                        $this->owner->{$rule} = $params[$attribute];
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Возвращает данные из методов GET/POST из обоих, или по отдельности, определяется флагом flag.
     *
     * @param int $flag
     * @return array
     */
    protected function getParams(int $flag = self::PARAMS_ON_GET | self::PARAMS_ON_POST): array
    {
        $params = [];
        if ($flag & self::PARAMS_ON_GET) {
            $params = array_replace($params, $this->request->get());
        }

        if ($flag & self::PARAMS_ON_POST) {
            $params = array_replace($params, $this->request->post());
        }

        return $params;
    }
}
