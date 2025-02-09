<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Frontend\Api\Controller;

use Gm;
use Gm\Http\Response;
use Gm\Mvc\Module\BaseModule;
use Gm\Mvc\Controller\Controller;

/**
 * Контроллер формирования API-ответа.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Frontend\Api\Controller
 * @since 1.0
 */
class Api extends Controller
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Frontend\Api\Module
     */
    public BaseModule $module;

    /**
     * {@inheritdoc}
     */
    public bool $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'verb' => [
                'class'    => '\Gm\Filter\VerbFilter',
                'autoInit' => true,
                'actions'  => [
                    '*' => ['POST', 'ajax' => false]
                ]
            ],
            'audit' => [
                'class'    => '\Gm\Panel\Behavior\AuditBehavior',
                'autoInit' => true,
                'allowed'  => '*',
                'enabled'  => true,
                'auditSections' => ['controller', 'module', 'request']
            ]
        ];
    }

    /**
     * Т.к. контроллер должен реализовать только одно действие 'index', то переопределяем 
     * его.
     * 
     * @param string|null $name Имя действия.
     * 
     * @return Api|string
     */
    public function action(string $name = null): static|string
    {
        $this->actionName = 'index';
        return $this;
    }

    /**
     * Возвращает HTTP-ответ.
     * 
     * HTTP-ответ должен иметь формат ответа "api" c 
     * форматтером {@see \Gm\Api\Response\ApiResponseFormatter}.
     * 
     * @param null|string $format Формат ответа (по умолчанию `null`). 
     * 
     * @return Response
     */
    public function getResponse(string $format = null): Response
    {
        if (!isset($this->response)) {
            $this->response = Gm::$app->response;
        }
        if ($this->response->format !== 'api') {
            $this->response
                ->addFormatter('api', '\Gm\Api\Response\ApiResponseFormatter')
                ->setFormat('api')
                ->getHeaders()
                    ->add('charset', Gm::$app->config->encoding['external']);
            $this->response
                ->sendCsrfToken = false;
        }
        return $this->response;
    }

    /**
     * Действие "index" формирует API-ответ на запрос.
     * 
     * @return Response
     */
    public function indexAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var \Gm\Frontend\Api\Model\ApiRecord $apiRecord */
        $apiRecord = $this->getModel('ApiRecord');

        /** @var string $apiRoute Маршрут запроса */
        $apiRoute = $this->module->getApiRoute();
        /** @var \Gm\Frontend\Api\Model\ApiRecord|null $api */
        $api = $apiRecord->getByRoute($apiRoute);

        // если маршрут не найден
        if (empty($api)) {
                $response->setStatusCode(404);
                $response->meta
                        ->error(
                            GM_MODE_DEV ? 'API route "' . $apiRoute . '" not found'  :  'Unable to execute API request: route not found',
                            'NOT_FOUND'
                        );
                return $response;
        }

        // если маршрут не доступен
        if ($api->isEnabled()) {
            // если идентификатор модуля неизвестен
            if (empty($api->ownerId)) {
                $response->setStatusCode(500);
                $response->meta
                        ->error(
                            GM_MODE_DEV ? 'API record has empty module or extension ID' :  'Unable to execute API request: route not found',
                            'OWNER_NOT_FOUND'
                        );
                return $response;
            }

            try {
                 /** @var \Gm\Api\Api $ownerApi */
                $ownerApi = $api->getOwnerApi();
                // если нет владельца
                if (empty($ownerApi)) {
                    $response->setStatusCode(500);
                    $response->meta
                            ->error(
                                GM_MODE_DEV ? 
                                sprintf('No module or extension found on specified ID "%s"', $api->ownerId) : 
                                'Unable to execute API request: route not found',
                                'OWNER_NOT_FOUND'
                            );
                    return $response;
                }
            } catch (\Exception $exception) {
                $response->setStatusCode(500);
                $response->meta
                        ->error($exception->getMessage(), 'OWNER_NOT_FOUND');
                return $response;
            }

            // проверка для режима `GM_MODE_PRO`
            if ($ownerApi === null) {
                $response->setStatusCode(500);
                $response->meta
                        ->error($response->getReasonPhrase(), 'OWNER_NOT_FOUND');
                return $response;
            }

            try {
                // попытка вызова
                if (($content = $ownerApi->apiCall()) === false) {
                    $code = $ownerApi->getErrorCode() ?: 500;
                    $response->setStatusCode($code);
                    $response->meta
                            ->error(
                                $ownerApi->getError() ?: $response->getReasonPhrase(),
                                $ownerApi->getStatus()
                            );
                    return $response;
                } else {
                    $response
                        ->meta->content($content);
                    $apiRecord->incCounter();
                }
            } catch (\Exception $exception) {
                $response->setStatusCode(500);
                $response->meta
                        ->error(
                            GM_MODE_DEV ? $exception->getMessage() : $response->getReasonPhrase(),
                            'OWNER_NOT_CALLED'
                        );
            }
        } else {
            $response->setStatusCode(403);
            $response->meta
                    ->error(
                        GM_MODE_DEV ? 
                        sprintf('API route "%s" forbidden', $apiRoute) : 
                        $response->getReasonPhrase(),
                        'FORBIDDEN'
                    );
        }
        return $response;
    }
}
