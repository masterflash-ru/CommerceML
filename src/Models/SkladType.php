<?php

namespace Mf\CommerceML\Models;

class SkladType extends Model
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
    }
}
