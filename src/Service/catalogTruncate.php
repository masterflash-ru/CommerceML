<?php

/*
*стандартный обработчик очистки каталога
*/

namespace Mf\CommerceML\Service;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;



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
    $a=0;
    $this->connection->Execute("truncate import_1c_tovar",$a,adExecuteNoRecords);
    if ($this->config["1c"]["enable_truncate_category"]){
        //разрешено ли очищать структуру категорий?
        $this->connection->Execute("truncate import_1c_category",$a,adExecuteNoRecords);
    }
    $this->connection->Execute("truncate import_1c_brend",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_price",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_price_type",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_store_type",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_store",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_file",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_scheme",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_requisites",$a,adExecuteNoRecords);
}
	
}
