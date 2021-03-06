<?php
namespace Mf\CommerceML\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\EventManager\EventManager;


/**
 */
class C1ControllerFactory implements FactoryInterface
{
public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
{
	$config = $container->get('Config');
    $SharedEventManager=$container->get('SharedEventManager');
	$EventManager=new EventManager($SharedEventManager);
	$EventManager->addIdentifiers(["simba.1c"]);
	return new $requestedName($config,$EventManager);
}
}