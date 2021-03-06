<?php
namespace Mf\CommerceML\Service;

/*
*стандартный обработчик раздела Import из 1С
*/
use Mf\CommerceML\Service\CommerceML;
use ADO\Service\RecordSet;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Mf\CommerceML\Lib\Func\CreateUrl;


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
    $translit=new CreateUrl();
    $reader = new CommerceML();
    //разбираем каталог
    $f=$this->filename;
    $reader->loadimportXml($f);
    
    $reader->parseScheme();
    $reader->parseOnlyChanges();
    $reader->parseProperties(); //характеристики товара
    $reader->parseCategories();
    $onlyChange=$reader->getOnlyChanges();
    $categories=$reader->getCategories();
    $scheme=$reader->getScheme();
    $properties=$reader->getProperties();
    
    //если полная перезагрузка, чистим все во временных таблицах
    if (!$onlyChange){
        $this->EventManager->trigger("catalogTruncate");
    }
    /*-------------------------------------------------------------*/
    //обработка схемы файлов-общая информация
    $a=0;
    $this->connection->Execute("truncate import_1c_scheme",$a,adExecuteNoRecords);
    /*характистики всега только полная замена*/
    $this->connection->Execute("truncate import_1c_properties",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_properties_list",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_tovar_properties",$a,adExecuteNoRecords);
    
    $rss=new RecordSet();
    $rss->CursorType = adOpenStatic;
    $rss->MaxRecords=0;
    $rss->Open("select * from import_1c_scheme",$this->connection);

    foreach ($scheme as $parameter=>$value){
        if ($parameter!="id"){
            $rss->AddNew();
            $rss->Fields->Item["parameter"]->Value=$parameter;
            $rss->Fields->Item["value"]->Value=$value;
            $rss->Update();
        }
    }
    $rss->Close();
    $rss=null;

    /*-------------------------------------------------------------*/
    //обрабатываем характеристики товара, справочник указан в начале 
    $rs=new RecordSet();
    $rs->CursorType = adOpenStatic;
    $rs->MaxRecords=0;
    $rs->Open("select * from import_1c_properties",$this->connection);
    
    $rs_list=new RecordSet();
    $rs_list->CursorType = adOpenStatic;
    $rs_list->MaxRecords=0;
    $rs_list->Open("select * from import_1c_properties_list",$this->connection);
    $properties_list=[];
    foreach ($properties as $prop){
        $rs->AddNew();
        $rs->Fields->Item["name"]->Value=$prop->name;
        $rs->Fields->Item["id1c"]->Value=$prop->id;
        $rs->Fields->Item["type"]->Value=$prop->type;
        //смотрим список значений, если есть
        if ($prop->type=="voc"){
            foreach ($prop->values as $id1c=>$value){
                $rs_list->AddNew();
                $rs_list->Fields->Item["id1c"]->Value=$id1c;
                $rs_list->Fields->Item["value"]->Value=$value;
                $rs_list->Fields->Item["import_1c_properties"]->Value=$prop->id;
                //накаптиваем значений характеристик из списка что бы потом вставить значение в товар
                $properties_list[$id1c]=$value;
                //$rs_list->Update();
            }
        }
        //$rs->Update();
    }
    $rs->UpdateBatch(adAffectAllChapters,false,true);
    $rs_list->UpdateBatch(adAffectAllChapters,false,true);
    $rs_list->Close();
    $rs->Close();
    $rs_list=null;
    $rs=null;
    
    

    /*-------------------------------------------------------------*/
    //частичная / полная загрузка, проверяем на существование узлов
    //храним то что есть в нашей базе
    $exists=[];
    $rs=new RecordSet();
    $rs->CursorType = adOpenStatic;
    $rs->MaxRecords=0;
    $rs->Open("select * from import_1c_category",$this->connection);
    while (!$rs->EOF){
        $exists[$rs->Fields->Item["id1c"]->Value]=[
            $rs->Fields->Item["id"]->Value,
            $rs->Fields->Item["subid"]->Value,
            $rs->Fields->Item["level"]->Value,
            $rs->Fields->Item["name"]->Value,
            $rs->Fields->Item["url"]->Value
        ];
        $rs->MoveNext();
    }
    //смотрим на предмет переименования категории
    foreach ($categories as $id_1c=>$item){
        if (isset($exists[$id_1c]) && $item->name!=$exists[$id_1c][3]){
            //есть измнение!
            $rs->Find("id1c='{$id_1c}'");
            $rs->Fields->Item["name"]->Value=$item->name;
            //если переименовали, логично сменить и URL, но для индексации это плохо, пока оставим старое значение!
            //$rs->Fields->Item["url"]->Value=$exists[$id_1c][4]."-".$translit($item->name);
            $rs->Fields->Item["flag_change"]->Value=2;   //флаг обновления записи
            $rs->Update();
            $exists[$id_1c][3]=$item->name;
        }
    }
    
    
    
    //смотрим что у нас в файле и удалим то чего нет в файле 1C
    //останется только та структура которая уже существует и нужная для добавления дерева
    foreach ($exists as $k=>$item){
        if (!array_key_exists($k,$categories)){
            unset($exists[$k]);
        }
    }
    $root_id_1c="";
    foreach ($categories as $id_1c=>$item){
        $url="";
        if (!array_key_exists($id_1c,$exists)){
            if (isset($exists[$item->parent][0])){
                $subid=$exists[$item->parent][0];
                $url=$exists[$item->parent][4]."-";
            } else {
                $subid=0;
            }
            if (isset($exists[$item->parent][2])){
                $level=$exists[$item->parent][2]+1;
                $url=$exists[$item->parent][4]."-";
            } else {
                $level=0;
            }
            $rs->AddNew();
            $rs->Fields->Item["name"]->Value=$item->name;
            $rs->Fields->Item["id1c"]->Value=$id_1c;
            $rs->Fields->Item["subid"]->Value=$subid;
            $rs->Fields->Item["level"]->Value=$level;
            $rs->Fields->Item["flag_change"]->Value=1;   //флаг новой записи
            $rs->Fields->Item["url"]->Value=$url.$translit($item->name);
            
            $rs->Update();
            //сохраним как существующий, что бы строить дерево далее
            $exists[$id_1c]=[
                $rs->Fields->Item["id"]->Value,
                $rs->Fields->Item["subid"]->Value,
                $rs->Fields->Item["level"]->Value,
                $rs->Fields->Item["name"]->Value,
                $rs->Fields->Item["url"]->Value,
            ];
        }
    }
    $rs->Close();
    $rs=null;
    //импорт самого товара
   /*
   структура записи товара
["0e823869-a4ba-11e9-a6c6-ebc9ac19720b"] => object(Mf\CommerceML\Models\Product)#338 (15) {
    ["id"] => string(36) "0e823869-a4ba-11e9-a6c6-ebc9ac19720b"
    ["name"] => string(44) "Миниатюрный Шнауцер 3 кг"
    ["sku"] => string(6) "163030"
    ["measure"] => string(0) ""
    ["description"] => string(0) ""
    ["quantity"] => int(0)
    ["sklad_quantity"] => array(0) {
    }
    ["price"] => array(0) {
    }
    ["category"] => string(36) "c20f2cc8-db55-11e8-8384-b8ee65eaf58a"
    ["requisites"] => array(3) {
      ["ВидНоменклатуры"] => string(21) "Сухие корма"
      ["ТипНоменклатуры"] => string(10) "Товар"
      ["Полное наименование"] => string(44) "Миниатюрный Шнауцер 3 кг"
    }
    ["properties"] => array(4) {
      ["3481a3bb-9cea-11e9-a6c3-e20120b585ab"] => string(36) "b502d19b-db4e-11e8-8384-b8ee65eaf58a"
      ["3481a3ba-9cea-11e9-a6c3-e20120b585ab"] => string(36) "12840e9e-a008-11e9-a6c4-82902062f9c3"
      ["3481a3bd-9cea-11e9-a6c3-e20120b585ab"] => string(44) "Миниатюрный Шнауцер 3 кг"
      ["56ea33d1-9fed-11e9-a6c4-82902062f9c3"] => string(6) "3 000"
    }
    ["images"] => array(1) {
      [0] => array(2) {
        ["path"] => string(85) "import_files/0e/0e823869a4ba11e9a6c6ebc9ac19720b_949decc4a4ba11e9a6c6ebc9ac19720b.jpg"
        ["weight"] => int(0)
      }
    }
    ["brend"] => array(0) {
    }
    ["status"] => string(0) ""
    ["vats"] => array(1) {
      ["НДС"] => float(20)
    }
  }
*/
    /*-------------------------------------------------------------*/
    $a=0;
    $this->connection->Execute("truncate import_1c_tovar",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_brend",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_file",$a,adExecuteNoRecords);
    $this->connection->Execute("truncate import_1c_requisites",$a,adExecuteNoRecords);
    $brends=[];
    $reader->parseProducts();

    $dir=rtrim($this->config["1c"]["temp1c"],"/")."/";
    $products=$reader->getProducts();
    /*сам товар*/
    $rs=new RecordSet();
    $rs->CursorType = adOpenStatic;
    $rs->Open("select * from import_1c_tovar",$this->connection);
    
    /*файлы к товару*/
    $rsf=new RecordSet();
    $rsf->CursorType = adOpenStatic;
    $rsf->Open("select * from import_1c_file",$this->connection);
    
    /*характеристики товара*/
    $rsp=new RecordSet();
    $rsp->CursorType = adOpenStatic;
    $rsp->Open("select * from import_1c_tovar_properties",$this->connection);
    
    /*дополнительные реквизиты товара*/
    $rsr=new RecordSet();
    $rsr->CursorType = adOpenStatic;
    $rsr->Open("select * from import_1c_requisites",$this->connection);
    

    foreach ($products as $tovar_1c_id=>$item){
        if (!empty($item->vats["НДС"])){
            $vat=$item->vats["НДС"];
        } else {
            $vat=0;
        }
        if (!isset($exists[$item->category][0]) || !$item->name){
            continue;
        }
        $brend_id="";
        //накапливаем бренды, позже занесем в базу
        if (!empty($item->brend)){
            $brends[(string)$item->brend["id"]]=(string)$item->brend["value"];
            $brend_id=(string)$item->brend["id"];
        }
        

        $rs->AddNew();
        $rs->Fields->Item["import_1c_category"]->Value=$exists[$item->category][0];
        $rs->Fields->Item["category_id1c"]->Value=$item->category;
        $rs->Fields->Item["name"]->Value=$item->name;
        $rs->Fields->Item["category"]->Value=$item->category;
        $rs->Fields->Item["sku"]->Value=$item->sku;
        $rs->Fields->Item["description"]->Value=$item->description;
        $rs->Fields->Item["measure"]->Value=$item->measure;
        $rs->Fields->Item["measure_ratio"]->Value=$item->measure_ratio;
        $rs->Fields->Item["id1c"]->Value=$tovar_1c_id;
        $rs->Fields->Item["url"]->Value=$translit($item->name);
        $rs->Fields->Item["import_1c_brend"]->Value=$brend_id;
        $rs->Fields->Item["status"]->Value=$item->status;
        $rs->Fields->Item["vat"]->Value=$vat;
        //$rs->Update();
        
        /*смотрим харктеристики и заменяем значения из справочника, если это список значений
        * обычные строчные значения просто оставляем как есть
        */
        foreach ($item->properties as $k=>$prop){
            $rsp->AddNew();
            //$rsp->Fields->Item["import_1c_tovar"]->Value=$rs->Fields->Item["id"]->Value;
            $rsp->Fields->Item["1c_tovar_id1c"]->Value=$tovar_1c_id;
            $rsp->Fields->Item["value"]->Value=$prop;
            $rsp->Fields->Item["property_id"]->Value=$k;                        //ID характеристики
            if(array_key_exists( $prop,$properties_list)){
                $rsp->Fields->Item["property_list_id"]->Value=$prop;            //ID значения характеристики из списка (варианта)
                $rsp->Fields->Item["value"]->Value=$properties_list[$prop];     //новое значение из справочника списка вариантов
            }
            //$rsp->Update();
        }
        //$rsp->UpdateBatch(adAffectAllChapters,false,true);
        
        //дополнительные реквизиты
        foreach ($item->requisites as $name=>$requisit){
            $rsr->AddNew();
            $rsr->Fields->Item["import_1c_tovar"]->Value=$tovar_1c_id;
            $rsr->Fields->Item["name"]->Value=$name;
            $rsr->Fields->Item["value"]->Value=$requisit;
            //$rsr->Update();
        }
        //$rsr->UpdateBatch(adAffectAllChapters,false,true);

        //сопутствующие файлы
        foreach ($item->images as $images) {
            if (!empty(trim($images["path"]))){
            $rsf->AddNew();
            $rsf->Fields->Item["file"]->Value=$dir.$images["path"];
            $rsf->Fields->Item["weight"]->Value=$images["weight"];
            $rsf->Fields->Item["import_1c_tovar"]->Value=$tovar_1c_id; //id товара в терминах 1С
            //$rsf->Update();
            }
        }
        //$rsf->UpdateBatch(adAffectAllChapters,false,true);
        
    }
    $rs->UpdateBatch(adAffectAllChapters,false,true);
    $rsf->UpdateBatch(adAffectAllChapters,false,true);
    $rsp->UpdateBatch(adAffectAllChapters,false,true);
    $rsr->UpdateBatch(adAffectAllChapters,false,true);
    //добавляем бренды, ечли есть
    $rsb=new RecordSet();
    $rsb->CursorType = adOpenStatic;
    $rsb->Open("select * from import_1c_brend",$this->connection);
    foreach ($brends as $id=>$name){
        $rsb->AddNew();
        $rsb->Fields->Item["id1c"]->Value=$id;
        $rsb->Fields->Item["name"]->Value=$name;
        $rsb->Fields->Item["url"]->Value=$translit($name);
        //$rsb->Update();
    }
    $rsb->UpdateBatch(adAffectAllChapters,false,true);
}
	
}
