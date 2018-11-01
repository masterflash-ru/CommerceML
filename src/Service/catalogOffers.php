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
    
    $reader->parsePriceTypes();
    $pricetype=$reader->getPriceTypes();

    $reader->parseProductsPrice();
    $products=$reader->getProducts();
    
    $reader->parseSkladTypes();
    $sklad=$reader->getSkladTypes();
    
    $this->connection->Execute("update import_1c_price_type set flag_change=0",$a,adExecuteNoRecords);
    $this->connection->Execute("update import_1c_sklad_type set flag_change=0",$a,adExecuteNoRecords);
    $this->connection->Execute("delete from import_1c_price",$a,adExecuteNoRecords);
    $this->connection->Execute("delete from import_1c_sklad",$a,adExecuteNoRecords);
    
    //типы прайсов
    $exists=[];
    $rs=new RecordSet();
    $rs->CursorType = adOpenKeyset;
    $rs->MaxRecords=0;
    $rs->Open("select * from import_1c_price_type",$this->connection);
    while (!$rs->EOF){
        $exists[$rs->Fields->Item["id1c"]->Value]=[
            $rs->Fields->Item["type"]->Value,
            $rs->Fields->Item["currency"]->Value
        ];
        $rs->MoveNext();
    }
    //смотрим на предмет переименования категории
    foreach ($pricetype as $id_1c=>$item){
        if (isset($exists[$id_1c]) && $item->type!=$exists[$id_1c][0]){
            //есть измнение!
            $rs->Find("id1c='{$id_1c}'");
            $rs->Fields->Item["type"]->Value=$item->type;
            $rs->Fields->Item["flag_change"]->Value=2;   //флаг обновления записи
            $rs->Update();
            $exists[$id_1c][1]=$item->type;
        }
    }
    //смотрим что у нас в файле и удалим то чего нет в файле 1C
    //останется только та структура которая уже существует и нужная для добавления дерева
    foreach ($exists as $k=>$item){
        if (!array_key_exists($k,$pricetype)){
            unset($exists[$k]);
        }
    }
    //грузим новые записи по типам цен
    foreach ($pricetype as $id_1c=>$item){
        if (!array_key_exists($id_1c,$exists)){
            $rs->AddNew();
            $rs->Fields->Item["type"]->Value=$item->type;
            $rs->Fields->Item["id1c"]->Value=$id_1c;
            $rs->Fields->Item["currency"]->Value=$item->currency;
            $rs->Fields->Item["flag_change"]->Value=1;   //флаг новой записи
            $rs->Update();
        }
    }

    //типы склада
    $exists=[];
    $rs=new RecordSet();
    $rs->CursorType = adOpenKeyset;
    $rs->MaxRecords=0;
    $rs->Open("select * from import_1c_sklad_type",$this->connection);
    while (!$rs->EOF){
        $exists[$rs->Fields->Item["id1c"]->Value]=[
            $rs->Fields->Item["type"]->Value,
        ];
        $rs->MoveNext();
    }
    //смотрим на предмет переименования категории
    foreach ($sklad as $id_1c=>$item){
        if (isset($exists[$id_1c]) && $item->type!=$exists[$id_1c][0]){
            //есть измнение!
            $rs->Find("id1c='{$id_1c}'");
            $rs->Fields->Item["type"]->Value=$item->type;
            $rs->Fields->Item["flag_change"]->Value=2;   //флаг обновления записи
            $rs->Update();
            $exists[$id_1c][1]=$item->type;
        }
    }
    //смотрим что у нас в файле и удалим то чего нет в файле 1C
    //останется только та структура которая уже существует и нужная для добавления дерева
    foreach ($exists as $k=>$item){
        if (!array_key_exists($k,$sklad)){
            unset($exists[$k]);
        }
    }
    //грузим новые записи по типам цен
    foreach ($sklad as $id_1c=>$item){
        if (!array_key_exists($id_1c,$exists)){
            $rs->AddNew();
            $rs->Fields->Item["type"]->Value=$item->type;
            $rs->Fields->Item["id1c"]->Value=$id_1c;
            $rs->Fields->Item["flag_change"]->Value=1;   //флаг новой записи
            $rs->Update();
        }
    }

    //добавление в каталог продукции цен и остатков
    $rs=new RecordSet();
    $rs->CursorType = adOpenKeyset;
    $rs->MaxRecords=0;
    $rs->Open("select * from import_1c_sklad",$this->connection);
    $rsm=new RecordSet();
    $rsm->CursorType = adOpenKeyset;
    $rsm->MaxRecords=0;
    $rsm->Open("select * from import_1c_price",$this->connection);

    foreach ($products as $tovar_id1c=>$item){
        $quantity=(int)$item->quantity;
        $this->connection->Execute("update import_1c_tovar set quantity={$quantity} where id1c='{$tovar_id1c}'",$a,adExecuteNoRecords);
        //остатки на складах для кахдого из товаров
        foreach ($item->sklad_quantity as $id=>$sklad_quantity){
            $rs->AddNew();
            $rs->Fields->Item["id1c"]->Value=$tovar_id1c;
            $rs->Fields->Item["import_1c_sklad_type"]->Value=$id;
            $rs->Fields->Item["quantity"]->Value=(int)$sklad_quantity;
            $rs->Update();
        }
        //все виды цен для каждого из товара
        foreach ($item->price as $id=>$price){
            $rsm->AddNew();
            $rsm->Fields->Item["id1c"]->Value=$tovar_id1c;
            $rsm->Fields->Item["import_1c_price_type"]->Value=$id;
            $rsm->Fields->Item["currency"]->Value=$price["currency"];
            $rsm->Fields->Item["price"]->Value=$price["value"];
            $rsm->Update();
        }
    }
}
	
}