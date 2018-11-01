<?php

namespace Mf\CommerceML;

use Zend\Mvc\MvcEvent;
use Zend\EventManager\Event;
use Mf\CommerceML\Service\catalogImport;
use Mf\CommerceML\Service\catalogOffers;
use Mf\CommerceML\Service\catalogTruncate;

class Module
{
    protected $ServiceManager;

public function getConfig()
{
    return include __DIR__ . '/../config/module.config.php';
}

public function onBootstrap(MvcEvent $event)
{
    $this->ServiceManager=$event->getApplication()-> getServiceManager();
    $config=$this->ServiceManager->get("Config");
    
    //смотрим нужен ли стандартный парсер
    if ($config["1c"]["standartParser"]){
        $eventManager = $event->getApplication()->getEventManager();
        $sharedEventManager = $eventManager->getSharedManager();
        // объявление слушателя для обработки 
        $sharedEventManager->attach("simba.1c", "catalogImport", [$this, 'catalogImport']);
        $sharedEventManager->attach("simba.1c", "catalogOffers", [$this, 'catalogOffers']);
        $sharedEventManager->attach("simba.1c", "catalogTruncate", [$this, 'catalogTruncate']);
    }
    
}


/*
слушает событие catalogImport
предназначен для стандартной обработки 1С файлов import
*/
public function catalogImport(Event $event)
{
	$service=$this->ServiceManager->build(catalogImport::class,["filename"=>$event->getParam("filename",NULL)]);
	return $service->Import();
}

/*
слушает событие catalogOffers
предназначен для стандартной обработки 1С файлов offers
*/
public function catalogOffers(Event $event)
{
	$service=$this->ServiceManager->build(catalogOffers::class,["filename"=>$event->getParam("filename",NULL)]);
	return $service->Import();
}

/*
слушает событие catalogTruncate
предназначен для стандартной обработки 1С файлов - очистка каталога полная
*/
public function catalogTruncate(Event $event)
{
	$service=$this->ServiceManager->build(catalogTruncate::class);
	return $service->Import();
}

}
