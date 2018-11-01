<?php

namespace Mf\CommerceML\Models;

use Exception as CommerceMLException;

class Product extends Model
{
    /**
     * @var string $id
     */
    public $id;

    /**
     * @var string $name
     */
    public $name;

    /**
     * @var string $sku
     */
    public $sku;

    /**
     * @var string $unit
     */
    public $unit;

    /**
     * @var string $description
     */
    public $description;

    /**
     * @var int $quantity
     */
    public $quantity;
    
    public $sklad_quantity;

    /**
     * @var array $price
     */
    public $price = [];

    /**
     * @var string $category
     */
    public $category;

    /**
     * @var array $requisites
     */
    public $requisites = [];

    /**
     * @var array $properties
     */
    public $properties = [];

    /**
     * @var array $images
     */
    public $images = [];
    
    public $brend=[];

    /**
     * Class constructor.
     *
     * @param \SimpleXMLElement $importXml
     * @param \SimpleXMLElement $offersXml
     */
    public function __construct(
        \SimpleXMLElement $importXml=null,
        \SimpleXMLElement $offersXml=null
    )
    {
        $this->name = '';
        $this->quantity = 0;
        $this->description = '';
        if (!is_null($importXml)){
            $this->loadImport($importXml);
        }
        if (!is_null($offersXml)){
            $this->loadOffers($offersXml);
        }
    }

    /**
     * Load primary data from import.xml.
     *
     * @param \SimpleXMLElement $xml
     *
     * @throws CommerceMLException
     * @return void
     */
    public function loadImport($xml)
    {
        $this->id = trim($xml->Ид);

        $this->name = trim($xml->Наименование);
        $this->description = trim($xml->Описание);

        $this->sku = trim($xml->Артикул);
        $this->unit = trim($xml->БазоваяЕдиница);

        if (!$xml->Группы) {
            //throw new CommerceMLException("The product has no category: {$this->id}");
        }

        $this->category = (string)$xml->Группы->Ид;

        if ($xml->ЗначенияРеквизитов) {
            foreach ($xml->ЗначенияРеквизитов->ЗначениеРеквизита as $value) {
                $name = (string)$value->Наименование;
                $this->requisites[$name] = (string)$value->Значение;
            }
        }

        if ($xml->Изготовитель) {
            $id = (string)$xml->Изготовитель->Ид;
            $value = (string)$xml->Изготовитель->Наименование;

            if ($value) {
                $this->brend["id"] = $id;
                $this->brend["value"] = $value;
            }
        }

        
        if ($xml->Картинка) {
            $weight = 0;
            foreach ($xml->Картинка as $image) {
                array_push($this->images, [
                    'path' => (string)$image,
                    'weight' => $weight++
                ]);
            }
        }

        if ($xml->ЗначенияСвойств) {
            foreach ($xml->ЗначенияСвойств->ЗначенияСвойства as $prop) {

                $id = (string)$prop->Ид;
                $value = (string)$prop->Значение;

                if ($value) {
                    $this->properties[$id] = $value;
                }
            }
        }
    }

    /**
     * Load primary data form offers.xml.
     *
     * @param \SimpleXMLElement $xml
     *
     * @return void
     */
    public function loadOffers($xml)
    {
        $this->id = trim($xml->Ид);
        if ($xml->Количество) {
            $this->quantity = (int)$xml->Количество;
        }
        if ($xml->Цены) {
            foreach ($xml->Цены->Цена as $price) {
                $id = (string)$price->ИдТипаЦены;

                $this->price[$id] = [
                    'type' => $id,
                    'currency' => (string)$price->Валюта,
                    'value' => (float)$price->ЦенаЗаЕдиницу
                ];
            }
        }
        //распределение по складам
        if ($xml->Склад){
            foreach ($xml->Склад as $sklad){
                $attr=$sklad->attributes();
                $this->sklad_quantity[(string)$attr->ИдСклада]=(int)$attr->КоличествоНаСкладе;
            }
            
        }
    }
}