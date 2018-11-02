<?php

namespace Mf\CommerceML\Models;
use DateTime;

class Scheme extends Model
{
    public $version;
    public $time_ISO8601;
    public $datetime;


    public function __construct(\SimpleXMLElement $xml)
    {
        $this->loadImport($xml);
    }

    private function loadImport($xml)
    {
        $attr=$xml->attributes();
        $this->version=(string)$attr->ВерсияСхемы;
        $this->time_ISO8601=(string)$attr->ДатаФормирования;
        if ($this->time_ISO8601){
            $date = new DateTime($this->time_ISO8601);
            $this->datetime=$date->format('Y-m-d H:i:s');
        }
    }
}
