<?php

namespace tests\_support;

use AttributeTypes\BaseAssert;
use Codeception\Exception\ModuleConfigException;
use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\Module\REST;
use Codeception\Util\HttpCode;
use Exception;

/**
 * Class ApiHelper
 */
class ApiHelper extends Module
{
    private const VERSION_DEFAULT = 15;
    private const PHONE_DEFAULT = '0011234567';
    private const PASSWORD_DEFAULT = '12345678';

    /**
     * Прежний телефон codeception@aizel.ru на момент внедрения может быть занят предыдущей реализацией тестов.
     * Поэтому необходимо завести второй адрес, чтобы у вновь созданного пользователя гарантированно был email.
     */
    private const EMAIL_DEFAULT = 'codeception2@aizel.ru';

    /**
     * Задаётся через tests/acceptance.suite.yml
     *
     * @var array
     */
    protected $config = [
        'url',
        'reload',
        'strict',
        'snapshot',
        'version',
        'user.email',
        'user.phone',
        'user.password',
        'user.token'
    ];

    /**
     * Обязательные заголовки
     *
     * @var array
     */
    protected $headerRequired = [
        'X-Udid' => 'codeception',
        'X-App-Token' => 'codeception',
        'X-Device-UUID' => 'codeception',
    ];

    /**
     * Авторизация по token
     *
     * @var array
     */
    protected $headerAuth = [
        'X-Token' => null,
    ];

    /**
     * @var REST
     */
    private $rest;

    /**
     * HOOK: used after configuration is loaded
     *
     * @throws ModuleException
     * @throws ModuleConfigException
     */
    public function _initialize(): void
    {
        // Token Header
        if ($this->config['user.token'] ?? false) {
            $this->headerAuth['X-Token'] = $this->config['user.token'];
        }

        // BaseUrl
        if ($this->config['url'] && $this->isReloadSnapshot()) {
            $this->getModuleRest()->_setConfig(['url' => $this->config['url']]);
        }

        // Version добавляем к url
        if ($version = $this->getApiVersion()) {
            $baseUrl = rtrim($this->getModuleRest()->_getConfig('url'), '/');
            if (!$this->isReloadSnapshot()) {
                $baseUrl .= '/test';
            }

            $this->getModuleRest()->_setConfig(['url' => $baseUrl . '/v' . $version . '/']);
        }
    }

    /**
     * @return REST
     * @throws ModuleException
     */
    protected function getModuleRest(): REST
    {
        if ($this->rest === null) {
            $this->rest = $this->getModule('REST');
        }

        return $this->rest;
    }

    /**
     * @return bool
     */
    public function isStrictResponse(): bool
    {
        return $this->config['strict'] ?? false;
    }

    /**
     * @return bool
     */
    public function isReloadSnapshot(): bool
    {
        return $this->config['reload'] ?? false;
    }

    /**
     * @return bool
     */
    public function isEnableSnapshot(): bool
    {
        return $this->config['snapshot'] ?? true;
    }

    /**
     * Возвращает индекс тестируемой версии.
     *
     * @return int
     */
    public function getApiVersion(): int
    {
        return preg_replace('#\D#', '', $this->config['version'] ?? self::VERSION_DEFAULT);
    }

    /**
     * @return string
     * @throws ModuleException
     */
    public function getBaseUrl(): string
    {
        /**
         * hook: если для секции REST url необъявлен, то значение будет искаться в модуле PhpBrowser.
         * Важно чтобы baseUrl вернул абсолютный путь.
         */
        $baseUrl = $this->getModuleRest()->_getConfig('url');

        // (http|https)
        if (strpos($baseUrl, 'http') !== 0) {
            try {
                $baseUrl = $this->getModule('PhpBrowser')->_getConfig('url') . $baseUrl;
            } catch (ModuleException $exception) {
            }
        }

        return $baseUrl;
    }

    /**
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function amHeaderRequired(): void
    {
        foreach ($this->headerRequired as $key => $value) {
            $this->getModuleRest()->deleteHeader($key);
            $this->getModuleRest()->haveHttpHeader($key, $value);
        }
    }

    /**
     * @param string|null $token
     * @throws ModuleException
     */
    public function amHeaderAuthenticated(string $token = null): void
    {
        $this->getApiVersion() > 16
            ? $this->doSignupWithPhone()
            : $this->doSignupWithPassword();

        foreach ($this->headerAuth as $key => $value) {
            $this->getModuleRest()->deleteHeader($key);
            $this->getModuleRest()->haveHttpHeader($key, $token ?? $value);
        }
    }

    /**
     * @throws ModuleException
     */
    public function amHeaderAuthenticatedDisable(): void
    {
        $this->headerAuth['X-Token'] = null;
        foreach ($this->headerAuth as $key => $value) {
            $this->getModuleRest()->deleteHeader($key);
        }
    }

    /**
     * @param \AttributeTypes\BaseAssert $assert
     * @throws ModuleException
     * @throws Exception
     */
    public function assertAttributeTypes(BaseAssert $assert): void
    {
        $assert->assertAttributeTypes($this->getModuleRest()->grabDataFromResponseByJsonPath('$.')[0] ?? []);
    }

    /**
     * Попытка создать учетную запись и получить token через мобильный телефон
     *
     * @throws ModuleException
     * @throws Exception
     */
    protected function doSignupWithPhone(): void
    {
        if ($this->headerAuth['X-Token']) {
            return;
        }

        // set header
        $this->amHeaderRequired();

        // send phone, response confirm
        $this->getModuleRest()->sendPOST('/user/phone-auth/', [
            'phone' => $this->config['user.phone'] ?? self::PHONE_DEFAULT,
        ]);

        // check code response
        $this->getModuleRest()->seeResponseCodeIs(HttpCode::OK);

        // response
        $response = $this->getModuleRest()->grabDataFromResponseByJsonPath('$.confirm')[0] ?? [];

        // send phone, response confirm
        $this->getModuleRest()->sendPOST('/user/phone-auth/', [
            'phone' => $this->config['user.phone'] ?? self::PHONE_DEFAULT,
            'code' => $response['code'] ?? null,
        ]);

        // check code response
        $this->getModuleRest()->seeResponseCodeIs(HttpCode::OK);

        // response token
        $this->headerAuth['X-Token'] = $this->getModuleRest()->grabDataFromResponseByJsonPath('$.token')[0] ?? null;

        // add profile
        $this->doUserProfile();

        // add address
        $this->doUserAddress();
    }

    /**
     * Попытка создать учетную запись и получить token через email и password
     *
     * @throws ModuleException
     * @throws Exception
     */
    protected function doSignupWithPassword(): void
    {
        if ($this->headerAuth['X-Token']) {
            return;
        }

        // set header
        $this->amHeaderRequired();

        // try signin
        $this->getModuleRest()->sendPOST('/user/signin/', [
            'email' => $this->config['user.email'] ?? self::EMAIL_DEFAULT,
            'password' => $this->config['user.password'] ?? self::PASSWORD_DEFAULT
        ]);

        $response = $this->getModuleRest()->grabDataFromResponseByJsonPath('$.token');
        if ($token = $response[0] ?? null) {
            $this->headerAuth['X-Token'] = $token;
            return;
        }

        // try signup
        $this->getModuleRest()->sendPOST('/user/signup/', [
            'email' => $this->config['user.email'] ?? self::EMAIL_DEFAULT,
            'password' => $this->config['user.password'] ?? self::PASSWORD_DEFAULT,
            'firstName' => 'codeception',
            'phone' => time(),
            'gender' => 0
        ]);

        // check code response
        $this->getModuleRest()->seeResponseCodeIs(200);

        // get token
        $response = $this->getModuleRest()->grabDataFromResponseByJsonPath('$.token');
        $this->headerAuth['X-Token'] = $response[0] ?? null;

        // add address
        $this->doUserAddress();
    }

    /**
     * Попытка добавить информация о пользователе
     *
     * @throws ModuleException
     * @throws Exception
     */
    protected function doUserProfile(): void
    {
        // set header login
        $this->amHeaderAuthenticated();

        // address
        $this->getModuleRest()->sendPUT('/user/', [
            'firstName' => 'codeception',
            'lastName' => 'codeception',
            'middleName' => 'codeception',
            'email' => $this->config['user.email'] ?? self::EMAIL_DEFAULT,
        ]);
    }

    /**
     * Попытка добавить адрес доставки
     *
     * @throws ModuleException
     * @throws Exception
     */
    protected function doUserAddress(): void
    {
        // set header login
        $this->amHeaderAuthenticated();

        // проверяем наличие адреса
        $this->getModuleRest()->sendGET('/user/address/');

        // смотрим что есть Москва, если нет то заведём.
        $response = $this->getModuleRest()->grabDataFromResponseByJsonPath('$.')[0] ?? [];
        if (count($response) > 1) {
            $settlementIds = array_column(array_column($response, 'settlement'), 'id');
            if (in_array(235407, $settlementIds, true)) {
                return;
            }
        }

        $data = [
            'firstName' => 'codeception',
            'lastName' => 'codeception',
            'middleName' => 'codeception',
            'settlement' => 235407,
            'street' => 'Ленина',
            'house' => '13',
        ];

        // в старых версиях необходимо было заполнять телефон для адреса
        if ($this->getApiVersion() < 17) {
            $data['phones'] = [self::PHONE_DEFAULT];
        }

        // address
        $this->getModuleRest()->sendPOST('/user/address/', $data);
    }
}
