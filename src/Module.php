<?php
/**
 * Модуль веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Frontend\Api;

use Gm;

/**
 * API (Application Programming Interface).
 * 
 * Модуль API передаёт соответствующие API-запросы от клиентов к модулям и их расширениям.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Frontend\Api
 */
class Module extends \Gm\Mvc\Module\FrontendModule
{
    /**
     * {@inheritdoc}
     */
    public string $id = 'gm.fe.api';

    /**
     * @see Module::getApiRoute()
     * 
     * @var string
     */
    protected string $apiRoute;

    /**
     * Возвращает маршрут для формирования API-ответа.
     * 
     * @return string
     */
    public function getApiRoute()
    {
        if (!isset($this->apiRoute)) {
            $this->apiRoute = urldecode(Gm::$app->urlManager->route);
        }
        return $this->apiRoute;
    }
}
