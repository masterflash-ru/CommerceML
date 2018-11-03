<?php
/**
 */

namespace Mf\CommerceML;

use Zend\ Router\ Http\ Literal;


return [

    'router' => [
        'routes' => [
            '1c' => [//работа с 1С
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/1c',
                    'defaults' => [
                        'controller' => Controller\C1Controller::class,
                        'action'     => 'index',
                    ],
                ],
            ],//1c
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\C1Controller::class => Controller\Factory\C1ControllerFactory::class,
        ],
    ],
    'service_manager' => [
            'factories' => [//сервисы-фабрики
                Service\catalogImport::class => Service\Factory\catalogImport::class,
                Service\catalogOffers::class => Service\Factory\catalogOffers::class,
                Service\catalogTruncate::class => Service\Factory\catalogTruncate::class,
            ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],

    
    "1c"=>[
        //логин/пароль для базовой аутентификации 1С
        "login"=>[
            "admin"=>"vfibyf",
            ],
        "temp1c"=>__DIR__."/../../../../data/1c/",
        "standartParser"=>true,
    ],
];