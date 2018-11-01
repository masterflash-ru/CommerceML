<?php
namespace Mf\CommerceML\Service\Factory;

use Interop\Container\ContainerInterface;

/*
Фабрика 
*/

class catalogTruncate
{

public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
{
    $connection=$container->get('ADO\Connection');
    $config = $container->get('Config');
        return new $requestedName($connection,$config,$options);
    }
}

