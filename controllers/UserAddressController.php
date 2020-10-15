<?php

namespace cookbook\controllers;

use api\v2\models\forms\UserAddressForm;
use api\v2\models\UserAddress;
use api\v2\models\UserAddressCollection;
use components\filters\auth\AutoLoginAuth;
use crmApi\CrmApi;
use crmApi\dto\UserAddressDto;
use user\components\UserAddressService;
use user\exceptions\UpdateUserAddressException;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class UserAddressController
 */
final class UserAddressController extends BaseController
{
    /**
     * @var UserAddressService
     */
    private $service;

    /**
     * UserAddressController constructor.
     *
     * @param string $id
     * @param Module $module
     * @param UserAddressService $service
     * @param CrmApi $api
     * @param array $config
     */
    public function __construct(string $id, Module $module, UserAddressService $service, CrmApi $api, array $config = [])
    {
        $this->service = $service;

        parent::__construct($id, $module, $api, $config);
    }

    /**
     * {@inheritDoc}
     */
    protected function behaviorAuthenticator(): array
    {
        return [
            'class' => AutoLoginAuth::class,
            'strict' => true,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function behaviorVerbs(): array
    {
        return [
            'class' => VerbFilter::class,
            'actions' => [
                'create' => ['POST'],
                'update' => ['PUT'],
                'delete' => ['DELETE'],
                '*' => ['GET'],
            ],
        ];
    }

    /**
     * @SWG\Get(
     *     path = "/user/address/",
     *     tags = {"Пользователи"},
     *     summary = "Метод получения списка адресов клиента.",
     *     security = {
     *         {"userAuth" = {}},
     *         {"appAuth" = {}},
     *         {"udid" = {}},
     *     },
     *     @SWG\Response(
     *         response = "200",
     *         description = "Возвращает список адресов клиента",
     *         @SWG\Schema(
     *             ref = "#/definitions/UserAddressCollection",
     *         ),
     *     ),
     * )
     *
     * @return UserAddressCollection
     */
    public function actionIndex(): UserAddressCollection
    {
        $collection = new UserAddressCollection();

        array_map(static function (UserAddressDto $dto) use ($collection) {
            $collection->attach(UserAddress::loadFromData($dto->toArray()));
        }, $this->service->findAll());

        return $collection;
    }

    /**
     * @SWG\Get(
     *     path="/user/address/{id}/",
     *     tags={"Пользователи"},
     *     summary="Метод получения адреса клиента по id.",
     *     security = {
     *         {"userAuth" = {}},
     *         {"appAuth" = {}},
     *         {"udid" = {}},
     *     },
     *     @SWG\Parameter(
     *         name = "id",
     *         description = "Идентификатор адреса клиента",
     *         in = "path",
     *         type = "integer",
     *         required = true,
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Возвращает адрес клиента",
     *         @SWG\Schema(
     *          ref="#/definitions/UserAddress"
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Адрес не найден",
     *         @SWG\Schema(
     *          ref="#/definitions/ExceptionResponse"
     *         ),
     *     ),
     * )
     * @param int $id
     * @return UserAddress
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    public function actionView(int $id): UserAddress
    {
        if ($dto = $this->service->findById($id)) {
            return UserAddress::loadFromData($dto->toArray());
        }

        throw new NotFoundHttpException('У текущего клиента адреса с указанным id не найдено');
    }

    /**
     * @SWG\Post(
     *     path="/user/address/",
     *     tags={"Пользователи"},
     *     summary="Метод добавления нового адреса клиента.",
     *     security = {
     *         {"userAuth" = {}},
     *         {"appAuth" = {}},
     *         {"udid" = {}},
     *     },
     *     @SWG\Parameter(
     *         name = "firstName",
     *         description = "Имя клиента",
     *         in = "formData",
     *         type = "string",
     *     ),
     *     @SWG\Parameter(
     *         name = "lastName",
     *         description = "Фамилия клиента",
     *         in = "formData",
     *         type = "string",
     *     ),
     *     @SWG\Parameter(
     *         name = "middleName",
     *         description = "Отчество клиента",
     *         in = "formData",
     *         type = "string",
     *     ),
     *     @SWG\Parameter(
     *         name = "phones[]",
     *         description = "Телефоны клиента",
     *         in = "formData",
     *         type = "array",
     *         collectionFormat = "multi",
     *         @SWG\Items(
     *          type = "string"
     *         ),
     *     ),
     *     @SWG\Parameter(
     *         name = "settlement",
     *         description = "Уникальный идентификатор населённого пункта",
     *         in = "formData",
     *         type = "integer",
     *         required = true,
     *     ),
     *     @SWG\Parameter(
     *         name = "street",
     *         description = "Улица",
     *         in = "formData",
     *         type = "string",
     *         required = true,
     *     ),
     *     @SWG\Parameter(
     *         name = "house",
     *         description = "Дом",
     *         in = "formData",
     *         type = "string",
     *         required = true,
     *     ),
     *     @SWG\Parameter(
     *         name = "apartment",
     *         description = "Номер квартиры",
     *         in = "formData",
     *         type = "string",
     *     ),
     *     @SWG\Parameter(
     *         name = "zipCode",
     *         description = "Почтовый индекс",
     *         in = "formData",
     *         type = "string",
     *     ),
     *     @SWG\Parameter(
     *         name = "default",
     *         description = "Адрес по умолчанию",
     *         in = "formData",
     *         type = "boolean",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Возвращает созданный адрес клиента.",
     *         @SWG\Schema(
     *          ref="#/definitions/UserAddress"
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Ошибка сохранения введённых данных",
     *         @SWG\Schema(
     *          ref="#/definitions/ExceptionResponse"
     *         ),
     *     ),
     * )
     *
     * @throws BadRequestHttpException
     */
    public function actionCreate(): UserAddress
    {
        $form = new UserAddressForm($this->service);
        $form->load($this->request->post(), '');
        if ($model = $form->save()) {
            return $model;
        }

        throw new BadRequestHttpException($form->getFirstError());
    }

    /**
     * @SWG\Put(
     *     path="/user/address/{id}/",
     *     tags={"Пользователи"},
     *     summary="Метод редактирования адреса клиента.",
     *     security = {
     *         {"userAuth" = {}},
     *         {"appAuth" = {}},
     *         {"udid" = {}},
     *     },
     *     @SWG\Parameter(
     *         name = "id",
     *         description = "Идентификатор адреса клиента",
     *         in = "path",
     *         type = "integer",
     *         required = true,
     *     ),
     *     @SWG\Parameter(
     *         name = "firstName",
     *         description = "Имя клиента",
     *         in = "formData",
     *         type = "string",
     *     ),
     *     @SWG\Parameter(
     *         name = "lastName",
     *         description = "Фамилия клиента",
     *         in = "formData",
     *         type = "string",
     *     ),
     *     @SWG\Parameter(
     *         name = "middleName",
     *         description = "Отчество клиента",
     *         in = "formData",
     *         type = "string",
     *     ),
     *     @SWG\Parameter(
     *         name = "phones[]",
     *         description = "Телефоны клиента",
     *         in = "formData",
     *         type = "array",
     *         collectionFormat = "multi",
     *         @SWG\Items(type="string"),
     *     ),
     *     @SWG\Parameter(
     *         name = "settlement",
     *         description = "Уникальный идентификатор населённого пункта",
     *         in = "formData",
     *         type = "integer",
     *     ),
     *     @SWG\Parameter(
     *         name = "street",
     *         description = "Улица",
     *         in = "formData",
     *         type = "string",
     *     ),
     *     @SWG\Parameter(
     *         name = "house",
     *         description = "Дом",
     *         in = "formData",
     *         type = "string",
     *     ),
     *     @SWG\Parameter(
     *         name = "apartment",
     *         description = "Номер квартиры",
     *         in = "formData",
     *         type = "string",
     *     ),
     *     @SWG\Parameter(
     *         name = "zipCode",
     *         description = "Почтовый индекс",
     *         in = "formData",
     *         type = "string",
     *     ),
     *     @SWG\Parameter(
     *         name = "default",
     *         description = "Адрес по умолчанию",
     *         in = "formData",
     *         type = "boolean",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Возвращает отредактированный адрес клиента.",
     *         @SWG\Schema(
     *          ref="#/definitions/UserAddress"
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Ошибка сохранения введённых данных",
     *         @SWG\Schema(
     *          ref="#/definitions/ExceptionResponse"
     *         ),
     *     ),
     * )
     * @param int $id
     * @return UserAddress
     * @throws BadRequestHttpException
     */
    public function actionUpdate(int $id): UserAddress
    {
        $form = new UserAddressForm($this->service, ['id' => $id]);
        $form->setScenario($form::SCENARIO_UPDATE);
        $form->load($this->request->post(), '');
        if ($model = $form->save()) {
            return $model;
        }

        throw new BadRequestHttpException($form->getFirstError());
    }

    /**
     * @SWG\Delete(
     *     path="/user/address/{id}/",
     *     tags={"Пользователи"},
     *     summary="Метод удаления адреса клиента.",
     *     security = {
     *         {"userAuth" = {}},
     *         {"appAuth" = {}},
     *         {"udid" = {}},
     *     },
     *     @SWG\Parameter(
     *         name = "id",
     *         description = "Идентификатор адреса клиента",
     *         in = "path",
     *         type = "integer",
     *         required = true,
     *     ),
     *     @SWG\Response(
     *         response = "200",
     *         description = "Возвращает успешность изменения данных",
     *         ref = "#/definitions/Success",
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Ошибка выполнения запроса",
     *         @SWG\Schema(
     *          ref="#/definitions/ExceptionResponse"
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Адрес не найден",
     *         @SWG\Schema(
     *          ref="#/definitions/ExceptionResponse"
     *         ),
     *     ),
     * )
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    public function actionDelete(int $id): Response
    {
        try {
            if ($this->service->delete($id)) {
                return $this->getResponseSuccess();
            }
        } catch (UpdateUserAddressException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        throw new NotFoundHttpException('Не найден указанный адрес клиента');
    }
}
