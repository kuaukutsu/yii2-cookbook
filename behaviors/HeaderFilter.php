<?php

namespace cookbook\behaviors;

use Yii;
use yii\base\Action;
use yii\base\ActionFilter;
use yii\base\Controller;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Request;

/**
 * Class HeaderFilter
 *
 * example:
 * [
 *  'header' => [
 *      'class' => HeaderFilter,
 *      'rules' => [
 *          [
 *              'class' => HeaderRuleInterface,
 *              'header' => 'X-Udid',
 *              'attribute' => 'udid',
 *              'except' => ['index'],
 *              'required' => true|false,
 *              'denyCallback' => static function () {
 *                  throw new BadRequestHttpException();
 *              },
 *          ],
 *          [
 *              HeaderRuleInterface::class
 *          ],
 *      ]
 *  ]
 * ]
 *
 * @property Controller $owner
 */
final class HeaderFilter extends ActionFilter
{
    /**
     * Правила которые должны быть применены.
     *
     * @var array<array>|HeaderRuleInterface[]
     */
    public $rules = [];

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
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if ($this->request === null) {
            $this->request = Yii::$app->getRequest();
        }

        foreach ($this->rules as $i => $rule) {
            if (is_array($rule)) {
                if (count($rule) === 1) {
                    $rule = isset($rule['class']) ? $rule : ['class' => $rule[0]];
                }

                $this->rules[$i] = Yii::createObject($rule);
            }
        }
    }

    /**
     * @param Action $action
     * @return bool
     * @throws BadRequestHttpException если denyCallback выкидывает исключение
     */
    public function beforeAction($action): bool
    {
        if (parent::beforeAction($action)) {
            if ($this->enable) {
                foreach ($this->rules as $rule) {
                    if ($rule->isActive($action->id) && !$this->matchRule($rule) && $rule->isRequired()) {
                        return $rule->isDenyCallback();
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param HeaderRuleInterface $rule
     * @return bool
     */
    protected function matchRule(HeaderRuleInterface $rule): bool
    {
        $headers = $this->request->getHeaders();
        if ($headers->has($rule->getHeader())) {
            $value = $headers->get($rule->getHeader());

            if ($rule->getValue() !== null) {
                return $value === $rule->getValue();
            }

            if (($attribute = $rule->getAttribute()) && $this->owner->hasProperty($attribute)) {
                $this->owner->{$attribute} = $value;
            }

            return true;
        }

        return false;
    }
}
