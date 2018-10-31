<?php
namespace Mf\CommerceML\Controller\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

use Mf\CommerceML\Service\Parser;
/**
 */
class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $parser=$container->get(Parser::class);
		return new $requestedName($parser);
    }
}

