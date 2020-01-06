<?php
/**
* обеспечение протокола обмена с 1С
*/

namespace Mf\CommerceML\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Exception;
use ZipArchive;
use Laminas\Session\Container as SessionContainer;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

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
            $view->setTemplate("mf/commerce-ml/c1/checkauth.phtml");
            return $view;
        }

        if($mode == "init"){
            //инициализация, получение параметров для обмена
            $view->setTemplate("mf/commerce-ml/c1/init.phtml");
            $session = new SessionContainer("commerceml");
            $path=rtrim($this->config["1c"]["temp1c"],"/");
            if (!is_readable($path)){
                mkdir($path,0777,true);
            }
            $zip=class_exists("ZipArchive");
            $session["zip"]=$zip;
            $session["step"]=0;
            if ($zip){
                $zip="zip=yes";
            }
            else {
                $zip="zip=no";
            }
            $view->setVariables(["limit"=>$this->get_limit(),"zip"=>$zip]);
            
            if (!is_readable($path."/flag_clear.txt")){
                //это флаг очистки временного каталога, через 12 часов если будет новый обмен
                file_put_contents($path."/flag_clear.txt",time());
            } else {
                $clear=(int)file_get_contents($path."/flag_clear.txt");
                if ($clear +$this->config["1c"]["clear_after_sec"] < time() ){
                    //чистим все файлы и папки во временном хранилище
                    $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator($this->config["1c"]["temp1c"],FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
                    foreach ($iterator as $path) {
                        if ($path->isDir()) {
                            @rmdir((string)$path);
                        } else {
                            @unlink((string)$path);
                        }
                    }
                }
            }
            return $view;
        }

        if($mode == "import"){
            //инициализация, получение параметров для обмена
            $view->setTemplate("mf/commerce-ml/c1/import.phtml");
            $rez="failure";
            $message="";
            if (empty($filename)){
                $view->setVariables(["rez"=>$rez,"message"=>"Ошибка обмена, не указано имя файла"]);
                return $view;
            }
            $session = new SessionContainer("commerceml");
            if ($session["zip"] && empty($session["step"])){
                //файлы в архиве, разархивируем и передаем "progress", что бы 1С вновь обратилась
                $session["step"]=1;
                $zip = new ZipArchive;
                if ($zip->open($session["zip"]) === true) {
                    $zip->extractTo($this->tmp);
                    $zip->close();
                   @unlink ($session["zip"]);
                    $rez="progress";
                } else {
                    $view->setVariables(["rez"=>$rez,"message"=>"Ошибка архива"]);
                    return $view;
                }
                $session["zip"]=false;
            } else {
                if (empty($session["step"])){
                    $session["step"]=1;
                }
                $rez="progress";
                switch ($session["step"]){
                    case 1:{
                        $session["step"]++;
                        if (false!==stripos($filename, "import")){
                            $this->EventManager->trigger("catalogImport",NULL,["filename"=>$this->tmp.$filename]);
                            unlink($this->tmp.$filename);
                        }
                        if (false!==stripos($filename, "offers")){
                            $this->EventManager->trigger("catalogOffers",NULL,["filename"=>$this->tmp.$filename]);
                            unlink($this->tmp.$filename);
                        }
                        break;
                    }
                    case 2:{
                        $rez="success";
                        $session["step"]++;
                        if (false!==stripos($filename, "import")){
                            $this->EventManager->trigger("catalogImportComplete");
                        }
                        if (false!==stripos($filename, "offers")){
                            $this->EventManager->trigger("catalogOffersComplete");
                        }
                        break;
                    }
                    default:{//на всякий случай
                        $rez="success";
                    }
                }
            }
            $view->setVariables(["rez"=>$rez,"message"=>$message]);
            return $view;
        }


        if($mode == "file"){
            //сам обмен, загрузка файлов
            $view->setTemplate("mf/commerce-ml/c1/file.phtml");
            $session = new SessionContainer("commerceml");

            if (empty($filename)){
                $view->setVariables(["rez"=>$rez,"message"=>"Ошибка обмена, не указано имя файла"]);
                return $view; 
            }

            $str =$this->getRequest()->getContent();
            if (false!==stripos($filename, "import_files") ) {
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
            if ($session["zip"]) {
                $session["zip"]=$this->tmp.$filename;
            }
        }	
        return $view;
    }


    /**
    * преобразует строку типа 2М в числовое значение в байтах
    * $value - строка вида 2М
    * возвращает число
    */  
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

    /**
    * получить минимальное значение объема файла который можно получить и обработать
    * возвращается объем в байтах
    **/
    protected function get_limit()
    {
        $post_max_size = $this->str2bytes(ini_get('post_max_size'));
        $upload_max_filesize = $this->str2bytes(ini_get('upload_max_filesize'));
        $memory_limit = $this->str2bytes(ini_get('memory_limit'));
        return min($post_max_size, $upload_max_filesize, $memory_limit);
    }

    /**
    * чистит от файлов и папок временную папку обмена с 1С
    * /
    protected function clearFolder()
    {
        try {
            $idir = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $this->tmp, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST );
        } catch (\UnexpectedValueException $e) { return;}

        foreach( $idir as $v ){
            if( $v->isDir() and $v->isWritable() ){
                $f = glob( $idir->key() . '/*.*' );
                if (!empty( $f )){
                    foreach($f as $f1){
                        @unlink ($f1);
                    }
                }
                @rmdir( $idir->key() );
            }
        } 
    }*/
}
