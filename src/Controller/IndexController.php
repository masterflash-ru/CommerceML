<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Mf\CommerceML\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Mf\CommerceML\CommerceML;

class IndexController extends AbstractActionController
{

public function __construct($parser=0)
{
    $this->parser=$parser;
}

    public function indexAction()
    {
        
        \Zend\Debug\Debug::dump(class_exists("ZipArchive"));
        return [];
        
        
        $import=realpath(__DIR__."/../../../../data/1c/import0_1.xml");
        $offers=realpath(__DIR__."/../../../../data/1c/offers.xml");

        $reader = new CommerceML();
        
        
        $reader->loadimportXml($import);


        $reader->parseCategories();
        $categories=$reader->getCategories();
            \Zend\Debug\Debug::dump($categories);   
       
        return new ViewModel();
    }
}
