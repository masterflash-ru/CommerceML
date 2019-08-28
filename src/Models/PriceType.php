<?php

namespace Mf\CommerceML\Models;

class PriceType extends Model
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $currency;
    
    /*налоги*/
    public $vats=[];

    /**
     * @param \SimpleXMLElement $xmlPriceType
     */
    public function __construct(\SimpleXMLElement $xmlPriceType)
    {
        $this->loadImport($xmlPriceType);
    }

    /**
     * @param SimpleXMLElement [$xmlPriceType]
     * @return void
     */
    private function loadImport($xmlPriceType)
    {
        $this->id = (string)$xmlPriceType->Ид;

        $this->type = (string)$xmlPriceType->Наименование;

        $this->currency = (string)$xmlPriceType->Валюта;

        $name=(string)$xmlPriceType->Налог->Наименование;
        $vat_in=(string)$xmlPriceType->Налог->УчтеноВСумме;
        if ($vat_in=="true"){
            $vat_in=true;
        } else {
            $vat_in=false;
        }
        $vat_akcis=(string)$xmlPriceType->Налог->Акциз;
        if ($vat_akcis=="true"){
            $vat_akcis=true;
        } else {
            $vat_akcis=false;
        }
        $this->vats[$name]=["vat_in"=>$vat_in,"vat_akcis"=>$vat_akcis];
    }
}
