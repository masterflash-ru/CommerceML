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
    $this->connection->Execute("truncate import_1c_category",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_brend",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_price",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_price_type",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_sklad_type",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_sklad",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_file",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_scheme",$a,adExecuteNoRecords);
    
    /*//чистим все файлы и папки во временном хранилище
    $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator($this->config["1c"]["temp1c"],FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $path) {
        if ($path->isDir()) {
            rmdir((string)$path);
        } else {
            unlink((string)$path);
        }
    }*/

}
	
}
