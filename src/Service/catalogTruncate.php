<?php
namespace Mf\CommerceML\Service;

/*
*стандартный обработчик очистки каталога
*/


class catalogTruncate
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

}
    
	
public function Import()
{

    //чистим все что есть в каталоге 
    $this->connection->Execute("delete from import_1c_category");
     $this->connection->Execute("delete from catalog_tovar where 1c>''");

    return ;
}
	
}



