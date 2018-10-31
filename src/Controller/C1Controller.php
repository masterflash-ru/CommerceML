<?php
/**
вывод страницы О компании в разрезе города/комплекса
*/

namespace Mf\CommerceML\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use ADO\Service\RecordSet;
use Application\Lib\Func\CreateUrl;
use ArtemsWay\CommerceML\CommerceML;

use Zend\Session\Container;

use Exception;

class C1Controller extends AbstractActionController
{
	protected $connection;
	protected $cache;
	protected $config;
	
	public function __construct( $connection,$cache,$config)
	{
		$this->cache=$cache;
		$this->connection=$connection;
		$this->config=$config;
		
	}
	
public function indexAction()
{
	//$_SERVER['PHP_AUTH_USER'] = 'admin';
//$_SERVER['PHP_AUTH_PW'] = "maibyf";
	$view=new ViewModel();
	$view->setTerminal(true);
	
	//просто обращаемся к сессии, что бы она инициализировалась, если не была инициализирована
	$container = new Container('1c');
	$container->item = '1c';


	
	if (!isset($_SERVER['PHP_AUTH_USER'])) {
		header('WWW-Authenticate: Basic realm=""');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Ошибка авторизации';
		exit;
	} else {
			if (!isset($this->config["1c"][$_SERVER['PHP_AUTH_USER']]) || $_SERVER['PHP_AUTH_PW']!=$this->config["1c"][$_SERVER['PHP_AUTH_USER']]){
			echo 'Ошибка авторизации';
			exit;
		}
	}
	
	
    $mode=$this->params()->fromQuery('mode',"");
	$type=$this->params()->fromQuery('type',"");
    $filename=$this->params()->fromQuery('filename',null);    
	
	
	if($mode == "checkauth"){//проверка соединения
		$view->setTemplate("mf/commerce-ml/c1/checkauth.phtml");return $view;
	}
	
	if($mode == "init"){
		//инициализация, получение параметров для обмена
		$view->setTemplate("mf/commerce-ml/c1/init.phtml");return $view;
	}
	if($mode == "import"){
		//инициализация, получение параметров для обмена
		$view->setTemplate("mf/commerce-ml/c1/import.phtml");
        
        if (empty($filename)){
            throw new  \Exception("Ошибка обмена, не указано имя файла");
        }
        $reader = new CommerceML();
        $translit=new CreateUrl;
        

        
        if (false!==stripos($filename, "offers") && $filename){
            //цены и остатки
            $f=__DIR__."/../../../../data/1c/".$filename;
            $reader->loadoffersXml($f);
            $reader->parseProductsPrice();
            $products=$reader->getProducts();
            $price_1c_id=$this->config["money_id_1c"];
            //\Zend\Debug\Debug::dump($products);
            $t=0;
            foreach ($products as $tovar_1c_id=>$item){
                if (!isset($item->price[$price_1c_id])){
                    $price=0;
                } else {
                    $price=str_replace(",",".",$item->price[$price_1c_id]["value"]);
                }
                $this->connection->Execute("update catalog_tovar set price='$price' where 1c='$tovar_1c_id'",$t,adExecuteNoRecords);
                
            }
            //@unlink($f);
        }
		$this->cache->clearByTags(["catalog_tovar","catalog_category"],true);//теги
		return $view;
	}


	if($mode == "file"){
		//сам обмен, загрузка файлов failure success
		$view->setTemplate("mf/commerce-ml/c1/file.phtml");
		try {
            if (empty($filename)){
                throw new  \Exception("Ошибка обмена, не указано имя файла");
            }
            
            if (false!==stripos($filename, "import_files") )  {
                //загрузка файлов, типа картинок
                $path=dirname(__DIR__."/../../../../data/1c/".$filename);

                if (!is_readable($path)){
                    mkdir($path,0777,true);
                }
                $str = file_get_contents('php://input');
            } else {
                //обработка XML файла
				$str = file_get_contents('php://input');
				$bom = pack("CCC", 0xef, 0xbb, 0xbf);
				if (0 === strncmp($str, $bom, 3)) {
						$str = substr($str, 3);
				}

            }

            file_put_contents(__DIR__."/../../../../data/1c/".$filename,$str);
            
            /*
            * странный протокол 1С, если грузим с картинками, то импортировать нужно именно на этом шаге
            * если без картинок, тогда отдельный запрос на импорт из файла
            */
            //только для первого шага, когда загружаем import чистим каталог
            if (false!==stripos($filename, "import") && false===stripos($filename, "import_files"))  {
                $this->importImport($filename);
                @unlink(__DIR__."/../../../../data/1c/".$filename);
            }

        }
		catch (Exception $e){
			//любая ошибка 
			
			echo "failure\n";
			echo print_r($e);
            exit;
		}
        
 
	}	
	
    
    return $view;

	

}

/*импорт номенклатуры
из файла import
$filename - имя файла для обработки
*/
protected function importImport($filename)
{
    $reader = new CommerceML();
    $translit=new CreateUrl;
    $ii=10;
    //чистим все что есть в каталоге 
    $this->connection->Execute("delete from catalog_category where 1c>''");
    //разбираем каталог
    $f=__DIR__."/../../../../data/1c/".$filename;
    $reader->loadimportXml($f);
    $reader->parseCategories();
    $categories=$reader->getCategories();
    //\Zend\Debug\Debug::dump($categories);

    $rs=new RecordSet();
    $rs->CursorType = adOpenKeyset;
    $rs->MaxRecords=0;
    $rs->Open("select * from catalog_category where 1c>''",$this->connection);
            
            $root_id_1c="";
            $cache=[];
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
                $rs->Fields->Item["public"]->Value=1;
                $rs->Fields->Item["poz"]->Value=$ii;
                
                
                if ($root_id_1c!=$item->parent){
                    $rs->Fields->Item["subid"]->Value=$cache[$item->parent][0];
                    $rs->Fields->Item["level"]->Value=$cache[$item->parent][1];
                    $rs->Fields->Item["url"]->Value=$translit($cache[$item->parent][2]."-".$item->name);
                }
                $rs->Update();
                $cache[$id_1c]=[$rs->Fields->Item["id"]->Value,$rs->Fields->Item["level"]->Value,$item->name];
                $ii+=10;
            }
            //импорт самого товара
            $reader->parseProducts();
            $products=$reader->getProducts();
            //\Zend\Debug\Debug::dump($products);
            $rs=new RecordSet();
            $rs->CursorType = adOpenKeyset;
            $rs->MaxRecords=0;
            $rs->Open("select * from catalog_tovar where 1c>''",$this->connection);
            foreach ($products as $tovar_1c_id=>$item){
                if (!isset($cache[$item->category][0]) || !$item->name){
                    continue;
                }
                $rs->AddNew();
                $rs->Fields->Item["catalog_category"]->Value=$cache[$item->category][0];
                $rs->Fields->Item["name"]->Value=$item->name;
                $rs->Fields->Item["code"]->Value=$item->sku;
                $rs->Fields->Item["1c"]->Value=$tovar_1c_id;
                $rs->Update();
            }
}
}
