<?php
/*
* содержит характристики товара
*/
namespace Mf\CommerceML\Models;

use Exception as CommerceMLException;

class Property
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
     * @var string $type
     */
    public $type;

    /**
     * @var array $values
     */
    public $values = [];

    /**
     * @param \SimpleXMLElement $importXml
     */
    public function __construct(\SimpleXMLElement $importXml)
    {
        if (!is_null($importXml)) {
            $this->loadImport($importXml);
        }
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return void
     */
    public function loadImport($xml)
    {
        $this->id = (string)$xml->Ид;

        $this->name = (string)$xml->Наименование;

        $this->type = $this->getType((string)$xml->ТипЗначений);
        //смотрим варианты значений, если есть
        if ($this->type === 'voc' && $xml->ВариантыЗначений) {
            foreach ($xml->ВариантыЗначений->Справочник as $value) {
                $id = (string)$value->ИдЗначения;
                $this->values[$id] = (string)$value->Значение;
            }
        }
    }

    public function getType($xmlType)
    {
        switch ($xmlType) {
            case 'Справочник':
                $type = 'voc';
                break;
            case 'Строка':
                $type = 'str';
                break;
            case 'Число':
                $type = 'int';
                break;
            default:
                throw new CommerceMLException("Unknown property type: {$xmlType}.");
        }

        return $type;
    }
}