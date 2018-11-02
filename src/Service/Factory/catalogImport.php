<?php
namespace Mf\CommerceML\Service\Factory;

use Interop\Container\ContainerInterface;
use Zend\EventManager\EventManager;
/*
Фабрика 
*/

class catalogImport
{

public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
{
    $connection=$container->get('ADO\Connection');
    $config = $container->get('Config');
    $SharedEventManager=$container->get('SharedEventManager');
	$EventManager=new EventManager($SharedEventManager);
	$EventManager->addIdentifiers(["simba.1c"]);

        return new $requestedName($connection,$config,$EventManager,$options);
    }
}

