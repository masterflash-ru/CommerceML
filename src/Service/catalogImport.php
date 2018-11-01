<?php
namespace Mf\CommerceML\Service;

/*
*стандартный обработчик раздела Import из 1С
*/
use Mf\CommerceML\Service\CommerceML;
use Mf\CommerceML\Lib\Func\CreateUrl;
use ADO\Service\RecordSet;

class catalogImport
{
	protected $options;
	protected $connection;
	protected $config;
    protected $filename;
    protected $EventManager;
	
public function __construct($connection,$config,$EventManager,$options) 
{
	$this->options=$options;
	$this->connection=$connection;
	$this->config=$config;
    $this->filename=$options["filename"];
    $this->EventManager=$EventManager;
}
    
	
public function Import()
{

    $reader = new CommerceML();
    $translit=new CreateUrl();
    //разбираем каталог
    $f=$this->filename;
    $reader->loadimportXml($f);
    
    $reader->parseOnlyChanges();
    $reader->parseCategories();
    $onlyChange=$reader->getOnlyChanges();
    $categories=$reader->getCategories();

    if (!$onlyChange){
        //чистим каталог, полная перезагрузка каталога
        $this->EventManager->trigger("catalogTruncate");
        $rs=new RecordSet();
        $rs->CursorType = adOpenKeyset;
        $rs->MaxRecords=0;
        $rs->Open("select * from import_1c_category limit 1",$this->connection);
        
        $root_id_1c="";
        $exists=[];
        foreach ($categories as $id_1c=>$item){
            /*if (!$item->parent){
                    $root_id_1c=$id_1c;
                    continue;
                }*/
            $rs->AddNew();
            $rs->Fields->Item["name"]->Value=$item->name;
            $rs->Fields->Item["title"]->Value=$item->name;
            $rs->Fields->Item["keywords"]->Value=$item->name;
            $rs->Fields->Item["description"]->Value="Раздел каталога: ".$item->name;
            $rs->Fields->Item["1c"]->Value=$id_1c;
            $rs->Fields->Item["subid"]->Value=0;
            $rs->Fields->Item["level"]->Value=0;
            $rs->Fields->Item["url"]->Value=$translit($item->name);
            $rs->Fields->Item["change"]->Value=1;
            //$rs->Fields->Item["poz"]->Value=0;
            
            if ($root_id_1c!=$item->parent){
                $rs->Fields->Item["subid"]->Value=$exists[$item->parent][0];
                $rs->Fields->Item["level"]->Value=$exists[$item->parent][2];
                $rs->Fields->Item["url"]->Value=$translit($exists[$item->parent][3]."-".$item->name);
            }
            $rs->Update();
            $exists[$id_1c]=[
                $rs->Fields->Item["id"]->Value,
                $rs->Fields->Item["subid"]->Value,
                $rs->Fields->Item["level"]->Value,
                $item->name
            ];
            
        }
    } else{
        //частичная загрузка, проверяем на существование узлов
        $exists=[];
        $rs=new RecordSet();
        $rs->CursorType = adOpenKeyset;
        $rs->MaxRecords=0;
        $rs->Open("select * from import_1c_category",$this->connection);
        while (!$rs->EOF){
            $exists[$rs->Fields->Item["1c"]->Value]=[
                $rs->Fields->Item["id"]->Value,
                $rs->Fields->Item["subid"]->Value,
                $rs->Fields->Item["level"]->Value
            ];
            $rs->MoveNext();
        }
        //смотрим что у нас в файле и удалим то чего нет в файле
        foreach ($exists as $k=>$item){
            if (!array_key_exists($k,$categories)){
                unset($exists[$k]);
            }
        }
        $root_id_1c="";
        foreach ($categories as $id_1c=>$item){
            if (!array_key_exists($id_1c,$exists)){
                if (isset($exists[$item->parent][0])){
                    $subid=$exists[$item->parent][0];
                } else {
                    $subid=0;
                }
                if (isset($exists[$item->parent][2])){
                    $level=$exists[$item->parent][2]+1;
                } else {
                    $level=0;
                }
                $rs->AddNew();
                $rs->Fields->Item["name"]->Value=$item->name;
                $rs->Fields->Item["title"]->Value=$item->name;
                $rs->Fields->Item["keywords"]->Value=$item->name;
                $rs->Fields->Item["description"]->Value="Раздел каталога: ".$item->name;
                $rs->Fields->Item["1c"]->Value=$id_1c;
                $rs->Fields->Item["subid"]->Value=$subid;
                $rs->Fields->Item["level"]->Value=$level;
                $rs->Fields->Item["url"]->Value=$translit($item->name);
                $rs->Fields->Item["change"]->Value=1;
                //$rs->Fields->Item["poz"]->Value=0;
                
                $rs->Update();
                //сохраним как существующий, что бы строить дерево
                $exists[$id_1c]=[
                        $rs->Fields->Item["id"]->Value,
                        $rs->Fields->Item["subid"]->Value,
                        $rs->Fields->Item["level"]->Value
                    ];

            }
        }

    }

            //импорт самого товара
            $reader->parseProducts();
            
            $products=$reader->getProducts();
    
            \Zend\Debug\Debug::dump($products);
            $rs=new RecordSet();
            $rs->CursorType = adOpenKeyset;
            $rs->MaxRecords=0;
            $rs->Open("select * from import_1c_tovar",$this->connection);
            foreach ($products as $tovar_1c_id=>$item){
                if (!isset($exists[$item->category][0]) || !$item->name){
                    continue;
                }
                $rs->AddNew();
                $rs->Fields->Item["import_1c_category"]->Value=$exists[$item->category][0];
                $rs->Fields->Item["name"]->Value=$item->name;
                $rs->Fields->Item["code"]->Value=$item->sku;
                $rs->Fields->Item["1c"]->Value=$tovar_1c_id;
                //$rs->Update();
            }

    return ;
}
	
}



