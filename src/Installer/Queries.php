<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * Файл конфигурации Карты SQL-запросов.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

return [
    'drop'   => ['{{api}}'],
    'create' => [
        '{{api}}' => function () {
            return "CREATE TABLE `{{api}}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `owner_id` int(11) unsigned DEFAULT NULL,
                `owner_type` varchar(20) DEFAULT NULL,
                `route` varchar(255) DEFAULT NULL,
                `note` varchar(255) DEFAULT NULL,
                `counter` int(11) unsigned DEFAULT '0',
                `enabled` tinyint(1) unsigned DEFAULT '1',
                PRIMARY KEY (`id`)
                ) ENGINE={engine} 
                DEFAULT CHARSET={charset} COLLATE {collate}";
        }
    ],

    'run' => [
        'install'   => ['drop', 'create'],
        'uninstall' => ['drop']
    ]
];