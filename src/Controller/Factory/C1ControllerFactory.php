<?php
namespace Mf\CommerceML\Controller\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;



/**
 */
class C1ControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $connection=$container->get('ADO\Connection');
		$config = $container->get('Config');
		$cache = $container->get('DefaultSystemCache');
		
		return new $requestedName( $connection,$cache,$config);
    }
}

