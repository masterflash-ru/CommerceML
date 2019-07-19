<?php
/**
* обеспечение протокола обмена с 1С
*/

namespace Mf\CommerceML\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Exception;
use ZipArchive;
use Zend\Session\Container as SessionContainer;

class C1Controller extends AbstractActionController
{
	protected $config;
    protected $tmp;
    protected $EventManager;
	
public function __construct($config,$EventManager)
{
	$this->config=$config;
    $this->EventManager=$EventManager;
    $this->tmp=realpath($config["1c"]["temp1c"])."/";
}
	
public function indexAction()
{
	$view=new ViewModel();
	$view->setTerminal(true);
    set_time_limit(0);
	
	
	if (!isset($_SERVER['PHP_AUTH_USER'])) {
		header('WWW-Authenticate: Basic realm=""');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Ошибка авторизации';
		exit;
	} else {
			if (!isset($this->config["1c"]["login"][$_SERVER['PHP_AUTH_USER']]) || $_SERVER['PHP_AUTH_PW']!=$this->config["1c"]["login"][$_SERVER['PHP_AUTH_USER']]){
			echo 'Ошибка авторизации';
			exit;
		}
	}
	
    $mode=$this->params()->fromQuery('mode',"");
	$type=$this->params()->fromQuery('type',"");
    $filename=$this->params()->fromQuery('filename',null);    
	
	if($mode == "checkauth"){//проверка соединения
        $session = new SessionContainer("commerceml");
		$view->setTemplate("mf/commerce-ml/c1/checkauth.phtml");return $view;
	}
	
	if($mode == "init"){
		//инициализация, получение параметров для обмена
		$view->setTemplate("mf/commerce-ml/c1/init.phtml");
        $path=rtrim($this->config["1c"]["temp1c"],"/");
        if (!is_readable($path)){
            mkdir($path,0777,true);
        } 
        $view->setVariable("limit",$this->get_limit());
        return $view;
	}
	if($mode == "import"){
		//инициализация, получение параметров для обмена
		$view->setTemplate("mf/commerce-ml/c1/import.phtml");
        
        if (empty($filename)){
            throw new  Exception("Ошибка обмена, не указано имя файла");
        }
        if (false!==stripos($filename, "import")){
            $this->EventManager->trigger("catalogImport",NULL,["filename"=>$this->tmp.$filename]);
            unlink($this->tmp.$filename);
            $this->EventManager->trigger("catalogImportComplete");
        }
        if (false!==stripos($filename, "offers")){
            $this->EventManager->trigger("catalogOffers",NULL,["filename"=>$this->tmp.$filename]);
            unlink($this->tmp.$filename);
            //вызывается общее событие после завершения загрузки во временные хранилища
            $this->EventManager->trigger("catalogOffersComplete");
        }
		return $view;
	}

    
	if($mode == "file"){
		//сам обмен, загрузка файлов
		$view->setTemplate("mf/commerce-ml/c1/file.phtml");
        $session = new SessionContainer("commerceml");
		try {
            if (empty($filename)){
                throw new  Exception("Ошибка обмена, не указано имя файла");
            }
            $str =$this->getRequest()->getContent();
            if (false!==stripos($filename, "import_files") )  {
                //загрузка файлов, типа картинок
                $path=dirname($this->tmp.$filename);

                if (!is_readable($path)){
                    mkdir($path,0777,true);
                }
            } else {
                //обработка XML файла
				$bom = pack("CCC", 0xef, 0xbb, 0xbf);
				if (0 === strncmp($str, $bom, 3)) {
						$str = substr($str, 3);
				}
            }
            //запишем переданный файл
            file_put_contents($this->tmp.$filename,$str,FILE_APPEND);
            //проверяем на zip, если да, то сразу разархивируем и удалим исходный архив
            //и смотрим все ли получили
            if (stripos($filename,".zip")>1 && $this->params()->fromHeader('Content-Length')->getFieldValue() !=$this->get_limit()){
                $zip = new ZipArchive;
                if ($zip->open($this->tmp.$filename) === true) {
                    $zip->extractTo($this->tmp);
                    $zip->close();
                } else {
                    echo "failure\n";
                    echo "Ошибка архива\n";
                    exit;
                }
                unlink($this->tmp.$filename);
            }
        } catch (Exception $e){
			//любая ошибка 
			echo "failure\n";
			echo print_r($e);
            exit;
		}
	}	
    return $view;
}

    
    
    
    
protected function str2bytes($value)
{
    $unit_byte = preg_replace('/[^a-zA-Z]/', '', $value);
    $num_val = preg_replace('/[^\d]/', '', $value);
    switch ($unit_byte) {
        case 'M':
            $k = 2;
            break;
        default:
            $k = 1;
    }
    return $num_val * pow(1024, $k);
}

protected function get_limit()
{
    $post_max_size = $this->str2bytes(ini_get('post_max_size'));
    $upload_max_filesize = $this->str2bytes(ini_get('upload_max_filesize'));
    $memory_limit = $this->str2bytes(ini_get('memory_limit'));
    return min($post_max_size, $upload_max_filesize, $memory_limit);
}

}
