<?php
/**
 */

namespace Mf\CommerceML;

use Zend\ Router\ Http\ Literal;
use Zend\ Router\ Http\ Segment;
use Zend\ ServiceManager\ Factory\ InvokableFactory;

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



            'test' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/test',
                    'defaults' => [
                        'controller' => Controller\ IndexController::class,
                        'action' => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\ IndexController::class => Controller\Factory\IndexControllerFactory::class,
            Controller\C1Controller::class => Controller\Factory\C1ControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'service_manager' => [
            'factories' => [//сервисы-фабрики
                Service\Parser::class => Service\Factory\ParserFactory::class,
            ],
        ],
    
    
    /*ID типа цены которую мы обрабатываем*/
    "money_id_1c"=>"5c44ee70-dc17-11e8-960e-001c4252ed46",
    //логин/пароль для базовой аутентификации 1С
    "1c"=>[
        "admin"=>"vfibyf",
    ],
];