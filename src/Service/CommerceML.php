<?php

namespace Mf\CommerceML\Service;

use Mf\CommerceML\Models\Property;
use Mf\CommerceML\Models\Category;
use Mf\CommerceML\Models\PriceType;
use Mf\CommerceML\Models\SkladType;
use Mf\CommerceML\Models\Product;
use Mf\CommerceML\Models\Scheme;
use Exception as CommerceMLException;

class CommerceML
{
    /**
     * Import xml document.
     *
     * @var \SimpleXMLElement
     */
    protected $importXml;

    /**
     * Offers xml document.
     *
     * @var \SimpleXMLElement
     */
    protected $offersXml;

    /**
     * Import time from import.xml.
     *
     * @var string
     */
    protected $importTime;

    /**
     * Contains only changes
     * from import.xml
     *
     * @var bool
     */
   protected $onlyChanges;

    /**
     * Categories from import.xml.
     *
     * @var array
     */
    protected $categories = [];

    /**
     * Properties from import.xml.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Products from import.xml.
     *
     * @var array
     */
    protected $products = [];

    /**
     * Price types from offers.xml.
     *
     * @var array
     */
    protected $priceTypes = [];
    
    protected $skladTypes = [];
    protected $scheme=[];


    /**
     * Load XML from file.
     *
     * @param string $path
     * @throws CommerceMLException
     *
     * @return \SimpleXMLElement
     */
    public function loadXml($path)
    {
        if (!is_file($path)) {
            throw new CommerceMLException("Wrong file path: {$path}.");
        }

        libxml_use_internal_errors(true);

        $importXml = simplexml_load_file($path);

        if ($error = libxml_get_last_error()) {
            throw new CommerceMLException("Simple xml load file error: {$error->message}.");
        }

        if (!$importXml) {
            throw new CommerceMLException("File was not loaded: {$importXml}.");
        }

        return $importXml;
    }

public function loadimportXml($f)
{
    $this->importXml=$this->loadXml($f);
}

 public function loadoffersXml($f)
{
    $this->offersXml=$this->loadXml($f);
}
   
    

    public function parseImportTime()
    {
        $importTime = $this->importXml['ДатаФормирования'];

        if (!$importTime) {
            throw new CommerceMLException('Attribute was not set: ДатаФормировния.');
        }

        $this->importTime = (string)$importTime;
    }

    /**
     * Parse contains only changes.
     *
     * @throws CommerceMLException
     * @return void
     */
    public function parseOnlyChanges()
    {
        $onlyChanges = $this->importXml->Каталог['СодержитТолькоИзменения'];

        if (!$onlyChanges) {
            throw new CommerceMLException('Attribute was not set: "СодержитТолькоИзменения".');
        }

        $this->onlyChanges = (string)$onlyChanges;
    }

    /**
     * Parse categories.
     *
     * @param \SimpleXMLElement|null $xmlCategories
     * @param \SimpleXMLElement|null $parent
     *
     * @throws CommerceMLException
     * @return void
     */
    public function parseCategories($xmlCategories = null, $parent = null)
    {
        if (is_null($xmlCategories)) {
            if (!$xmlCategories = $this->importXml->Классификатор->Группы) {
                throw new CommerceMLException('Categories not found.');
            }
        }

        foreach ($xmlCategories->Группа as $xmlCategory) {
            $category = new Category($xmlCategory);

            if (!is_null($parent)) {
                $parent->addChild($category);
            }

            $this->categories[$category->id] = $category;

            if ($xmlCategory->Группы) {
                $this->parseCategories($xmlCategory->Группы, $category);
            }
        }
    }

    /**
     * Parse properties.
     *
     * @return void
     */
    public function parseProperties()
    {
        if ($this->importXml->Классификатор->Свойства) {
            foreach ($this->importXml->Классификатор->Свойства->Свойство as $xmlProperty) {
                $property = new Property($xmlProperty);
                $this->properties[$property->id] = $property;
            }

        }
    }

    /**
     * Parse price types.
     *
     * @return void
     */
    public function parsePriceTypes()
    {
        if ($this->offersXml->ПакетПредложений->ТипыЦен) {
            foreach ($this->offersXml->ПакетПредложений->ТипыЦен->ТипЦены as $xmlPriceType) {
                $priceType = new PriceType($xmlPriceType);
                $this->priceTypes[$priceType->id] = $priceType;
            }
        }
    }

    public function parseSkladTypes()
    {
        if ($this->offersXml->ПакетПредложений->Склады) {
            foreach ($this->offersXml->ПакетПредложений->Склады->Склад as $xmlSkladType) {
                $skladType = new SkladType($xmlSkladType);
                $this->skladTypes[$skladType->id] = $skladType;
            }
        }
    }

    /*
    * парсер общей информации схемы
    */
    public function parseScheme()
    {
        $this->scheme = new Scheme($this->importXml);
        $this->scheme=(array)$this->scheme;
    }


    
    /**
     * Parse products.
     */
    public function parseProducts()
    {
        $buffer = [
            'products' => []
        ];

        $products = $this->importXml->Каталог->Товары;

        if (!$products) throw new CommerceMLException('Products not found.');

        // Parse products in import.xml.
        foreach ($products->Товар as $product) {
            $productId = (string)$product->Ид;
            $buffer['products'][$productId]['import'] = $product;
        }

        // Merge import and offer to one product.
        foreach ($buffer['products'] as $item) {
            $import = isset($item['import']) ? $item['import'] : null;

            if (is_null($import)) {
                continue;
            }

            $product = new Product($import,null);

            $this->products[$product->id] = $product;

            // Associate properties with the category.
            if (count($properties = $product->properties)) {
                $this->addPropertiesToCategory(
                    $product->category,
                    array_keys($properties)
                );
            }
        }
    }

    /**
     * Parse productsPrice.
     */
    public function parseProductsPrice()
    {
        $buffer = [
            'products' => []
        ];

        $import=null;
        $offers = $this->offersXml->ПакетПредложений->Предложения;

        if (!$offers) throw new CommerceMLException('Offers not found.');

        // Parse offers in offers.xml.
        foreach ($offers->Предложение as $offer) {
            $productId = (string)$offer->Ид;
            $buffer['products'][$productId]['offer'] = $offer;
        }

        // Merge import and offer to one product.
        foreach ($buffer['products'] as $item) {
            $offer = isset($item['offer']) ? $item['offer'] : null;

            if (is_null($offer)) {
                continue;
            }

            $product = new Product($import, $offer);

            $this->products[$product->id] = $product;

            // Associate properties with the category.
            if (count($properties = $product->properties)) {
                $this->addPropertiesToCategory(
                    $product->category,
                    array_keys($properties)
                );
            }
        }
    }

    /**
     * Add properties to the category.
     *
     * @param string $id
     * @param array $properties
     *
     * @return void
     */
    public function addPropertiesToCategory($id, $properties)
    {
        if (isset($this->categories[$id])) {
            $properties = array_merge($this->categories[$id]->properties, $properties);
            $this->categories[$id]->properties = array_unique($properties);
        }
    }

    /**
     * Get import time.
     *
     * @return string
     */
    public function getImportTime()
    {
        return $this->importTime;
    }

    /**
     * Check if file contains changes only.
     *
     * @return bool
     */
    public function getOnlyChanges()
    {
        return $this->onlyChanges == 'true';
    }

    /**
     * Get categories.
     *
     * @return array|Category[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Get properties.
     *
     * @return array|Property[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Get price types.
     *
     * @return array|PriceType[]
     */
    public function getPriceTypes()
    {
        return $this->priceTypes;
    }

    public function getSkladTypes()
    {
        return $this->skladTypes;
    }


    /**
     * Get products.
     *
     * @return array|Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }
    public function getScheme()
    {
        return $this->scheme;
    }

}
