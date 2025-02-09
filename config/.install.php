<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * Файл конфигурации установки модуля.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

return [
    'id'          => 'gm.fe.api',
    'use'         => FRONTEND,
    'name'        => 'API',
    'description' => 'Application Programming Interface',
    'namespace'   => 'Gm\Frontend\Api',
    'path'        => '/gm/gm.fe.api',
    'route'       => 'api', // использует BACKEND
    'routes'      => [
        [
            'use'     => FRONTEND,
            'type'    => 'crudSegments',
            'options' => [
                'module' => 'gm.fe.api',
                'route'  => 'api',
                'redirect' => [
                    '*@*' => ['api', 'index']
                ]
            ]
        ],
        [
            'use'     => BACKEND,
            'type'    => 'crudSegments',
            'options' => [
                'module' => 'gm.fe.api',
                'route'  => 'api',
                'prefix' => BACKEND
            ]
        ]
    ],
    'locales'     => ['ru_RU', 'en_GB'],
    'permissions' => ['info'],
    'events'      => [],
    'required'    => [
        ['php', 'version' => '8.2'],
        ['app', 'code' => 'GM MS'],
        ['app', 'code' => 'GM CMS']
    ]
];
