<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Frontend\Api\Model;

use Gm;
use Closure;
use Gm\Db\Sql\Where;
use Gm\Db\Sql\Select;
use Gm\Db\ActiveRecord;

/**
 * Класс активной записи API маршрута.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Frontend\Api\Model
 * @since 1.0
 */
class ApiRecord extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public function primaryKey(): string
    {
        return 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function tableName(): string
    {
        return '{{api}}';
    }

    /**
     * {@inheritdoc}
     */
    public function maskedAttributes(): array
    {
        return [
            'id'        => 'id', // уникальный идентификатор
            'ownerId'   => 'owner_id', // идентификатор владельца
            'ownerType' => 'owner_type', // вид владельца
            'route'     => 'route', // маршрут
            'counter'   => 'counter', // счётчик вызова API
            'enabled'   => 'enabled' // доступность маршрута
        ];
    }

    /**
     * {@inheritdoc}
     * 
     * @param bool $accessible Если `true`, возвратит все доступные элементы главного 
     *     меню для текущей роли пользователя (по умолчанию `true`).
     */
    public function fetchAll(
        string $fetchKey = null, 
        array $columns = ['*'], 
        Where|Closure|string|array|null $where = null, 
        string|array|null $order = null,
        bool $accessible = true
    ): array
    {
        /** @var Select $select */
        $select = $this->select($columns, $where);
        if ($order)
            $select->order($order);
        return $this
            ->getDb()
                ->createCommand($select)
                    ->queryAll($fetchKey);
    }

    /**
     * Возвращает запись по указанному значению первичного ключа.
     * 
     * @see ActiveRecord::selectByPk()
     * 
     * @param int|string $id Идентификатор записи.
     * 
     * @return ApiRecord|null Активная запись при успешном запросе, иначе `null`.
     */
    public function get(mixed $identifier): ?static
    {
        return $this->selectByPk($identifier);
    }

    /**
     * Возвращает параметры владельца (модуль, расширение) маршрута.
     * 
     * @return array
     */
    public function getOwnerParams(): array
    {
        if (empty($this->ownerId) || empty($this->ownerType)) {
            return [];
        }

        if ($this->ownerType === 'module') {
            /** @var \Gm\ModuleManager\ModuleRegistry $registry */
            $registry = Gm::$app->modules->getRegistry();
            return $registry->getAtMap($this->ownerId, null, []);
        } else
        if ($this->ownerType === 'extension') {
            /** @var \Gm\ExtensionManager\ExtensionRegistry $registry */
            $registry = Gm::$app->extensions->getRegistry();
            return $registry->getAtMap($this->ownerId, null, []);
        }
        return [];
    }

    /**
     * Возвращает объект API владельца (модуля или расширения).
     * 
     * @return \Gm\Api\Api|null
     */
    public function getOwnerApi()
    {
        $params = $this->getOwnerParams();

        if (empty($params) || empty($this->route)) {
            return null;
        }

        if ($this->ownerType === 'module') {
            /** @var \Gm\ModuleManager\ModuleManager $owner */
            $owner = Gm::$app->modules;
        } else
        if ($this->ownerType === 'extension') {
            /** @var \Gm\ExtensionManager\ExtensionManager $owner */
            $owner = Gm::$app->extensions;
        } else
            return null;

        return $owner->getObject(
            'Api\Api', $params['id'], ['route' => $this->route, 'owner' => $params]
        );
    }

    /**
     * Проверят, доступен ли маршрут для вызова API модуля.
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled > 0;
    }

    /**
     * Возвращает запись по указанному маршруту.
     * 
     * @see ActiveRecord::selectOne()
     * 
     * @param string $route Маршрут к API модуля.
     * 
     * @return ApiRecord|null Активная запись при успешном запросе, иначе `null`.
     */
    public function getByRoute(string $route): ?static
    {
        return $this->selectOne(['route' => $route]);
    }

    /**
     * Увеличивает счётчик вызова API модуля.
     * 
     * @return void
     */
    public function incCounter(): void
    {
        if ($this->counter > 0)
            $this->counter = $this->counter + 1;
        else
            $this->counter = 1;
        $this->update();
    }

    /**
     * Возвращает все записи (элементы) главного меню с указанным ключом.
     * 
     * Ключом каждой записи является значение первичного ключа {@see ActiveRecord::tableName()} 
     * текущей таблицы.
     * 
     * @see Menu::fetchAll()
     * 
     * @param bool $caching Указывает на принудительное кэширование. Если служба кэширования 
     *     отключена, кэширование не будет выполнено (по умолчанию `true`).
     * 
     * @return array
     */
    public function getAll(bool $caching = true): ?array
    {
        if ($caching)
            return $this->cache(
                function () { return $this->fetchAll($this->primaryKey(), $this->maskedAttributes(), ['enabled' => 1]); },
                null,
                true
            );
        else
            return $this->fetchAll($this->primaryKey(), $this->maskedAttributes(), ['enabled' => 1]);
    }
}
