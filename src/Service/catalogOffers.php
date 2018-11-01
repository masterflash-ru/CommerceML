<?php
namespace Mf\CommerceML\Service;

/*
*стандартный обработчик раздела Offers из 1С
*/
use Mf\CommerceML\Service\CommerceML;
use ADO\Service\RecordSet;


class catalogOffers
{
	protected $options;
	protected $connection;
	protected $config;
    protected $filename;
	
public function __construct($connection,$config,$options) 
{
	$this->options=$options;
	$this->connection=$connection;
	$this->config=$config;
    $this->filename=$options["filename"];
}
    
	
public function Import()
{
    $reader = new CommerceML();
    //разбираем каталог
    $f=$this->filename;
    $reader->loadoffersXml($f);
    
    //$reader->parseOnlyChanges();
    $reader->parsePriceTypes();
    //$onlyChange=$reader->getOnlyChanges();
    $pricetype=$reader->getPriceTypes();
    $reader->parseProductsPrice();
    
    $products=$reader->getProducts();
    
\Zend\Debug\Debug::dump($pricetype);
\Zend\Debug\Debug::dump($products);
    return ;
}
	
}



