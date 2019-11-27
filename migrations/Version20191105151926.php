<?php

namespace Mf\CommerceML;

use Mf\Migrations\AbstractMigration;
use Mf\Migrations\MigrationInterface;

class Version20191105151926 extends AbstractMigration implements MigrationInterface
{
    public static $description = "Migration description";

    public function up($schema, $adapter)
    {
        switch ($this->db_type){
            case "mysql":{
                $this->addSql("CREATE TABLE `import_1c_brend` (
                      `id1c` char(127) DEFAULT NULL,
                      `name` char(255) DEFAULT NULL,
                      `url` char(127) DEFAULT NULL,
                      KEY `id1c` (`id1c`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='производители'");
                $this->addSql("CREATE TABLE `import_1c_category` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `subid` int(11) DEFAULT NULL,
                      `level` int(11) DEFAULT NULL,
                      `name` char(255) DEFAULT NULL,
                      `id1c` char(127) DEFAULT NULL,
                      `flag_change` int(11) DEFAULT NULL COMMENT '1 - если были изменения',
                      `url` char(127) DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      KEY `subid` (`subid`,`level`),
                      KEY `name` (`name`),
                      KEY `change` (`flag_change`),
                      KEY `id1c` (`id1c`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='категории'");
                $this->addSql("CREATE TABLE `import_1c_file` (
                      `import_1c_tovar` char(127) NOT NULL COMMENT 'ID товара в терминах 1C',
                      `file` varchar(1000) DEFAULT NULL COMMENT 'сам файл+ путь',
                      `weight` int(11) DEFAULT NULL,
                      KEY `import_1c_tovar` (`import_1c_tovar`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='файлы к товару'");
                $this->addSql("CREATE TABLE `import_1c_price` (
                      `id1c` char(127) NOT NULL COMMENT 'ID товара в 1С',
                      `import_1c_price_type` char(127) DEFAULT NULL COMMENT 'ID типа прайса в 1С',
                      `currency` char(3) DEFAULT NULL,
                      `price` decimal(11,2) DEFAULT NULL COMMENT 'сама цена',
                      KEY `import_1c_price_type` (`import_1c_price_type`),
                      KEY `id1c` (`id1c`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='сами прайсы'");
                $this->addSql("CREATE TABLE `import_1c_price_type` (
                      `id1c` char(127) NOT NULL COMMENT 'ID 1С прайса',
                      `type` char(255) DEFAULT NULL COMMENT 'Имя цены',
                      `currency` char(20) DEFAULT NULL COMMENT 'Валюта',
                      `vat_name` char(50) DEFAULT NULL COMMENT 'имя налога, напр. НДС',
                      `vat_in` tinyint(4) DEFAULT NULL COMMENT 'налог включен в сумму (да/нет-1/0)',
                      `flag_change` int(11) DEFAULT NULL COMMENT '1-новая, 2-изменение'
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='типы прайсов'");
                $this->addSql("CREATE TABLE `import_1c_properties` (
                      `id1c` char(127) NOT NULL,
                      `name` char(127) DEFAULT NULL COMMENT 'имя характеристики',
                      `type` char(127) DEFAULT NULL COMMENT 'тип',
                      PRIMARY KEY (`id1c`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='справочник характеристик всех'");
                
                $this->addSql("CREATE TABLE `import_1c_properties_list` (
                      `id1c` char(127) NOT NULL,
                      `import_1c_properties` char(127) NOT NULL COMMENT 'ID характеристики, кому принадлежит',
                      `value` char(255) DEFAULT NULL COMMENT 'значение',
                      PRIMARY KEY (`id1c`,`import_1c_properties`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='варианты значений'");
                $this->addSql("CREATE TABLE `import_1c_requisites` (
                      `import_1c_tovar` char(127) NOT NULL COMMENT 'ID из 1С товара',
                      `name` char(100) NOT NULL COMMENT 'имя параметра',
                      `value` text COMMENT 'значение параметра',
                      PRIMARY KEY (`import_1c_tovar`,`name`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='дополнительные реквиз. товара'");
                $this->addSql("CREATE TABLE `import_1c_scheme` (
                      `parameter` char(255) NOT NULL COMMENT 'имя параметра',
                      `value` char(255) DEFAULT NULL COMMENT 'значение параметра',
                      PRIMARY KEY (`parameter`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='общая информация по схеме'");
                $this->addSql("CREATE TABLE `import_1c_store` (
                      `id1c` char(127) DEFAULT NULL COMMENT 'ID товара в 1С',
                      `import_1c_store_type` char(127) DEFAULT NULL COMMENT 'ID типа склада в 1С',
                      `quantity` int(11) DEFAULT NULL COMMENT 'остаток'
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='остатки на складах'");
                $this->addSql("CREATE TABLE `import_1c_store_type` (
                      `id1c` char(127) NOT NULL COMMENT 'ID 1С прайса',
                      `type` char(255) DEFAULT NULL COMMENT 'Имя цены',
                      `flag_change` int(11) DEFAULT NULL COMMENT '1-новая, 2-изменение'
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='типы складов'");
                $this->addSql("CREATE TABLE `import_1c_tovar` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `import_1c_category` int(11) DEFAULT NULL COMMENT 'ID категории сайта (число)',
                      `category_id1c` char(127) DEFAULT NULL,
                      `import_1c_brend` char(127) DEFAULT NULL COMMENT 'ID 1C производителя',
                      `id1c` char(127) DEFAULT NULL,
                      `name` char(255) DEFAULT NULL,
                      `sku` char(127) DEFAULT NULL,
                      `measure` int(11) DEFAULT NULL COMMENT 'базовая единица (шт.л.)',
                      `measure_ratio` int(11) DEFAULT NULL COMMENT 'коэффициент ед. измерерия',
                      `description` text,
                      `quantity` int(11) DEFAULT NULL COMMENT 'остаток общий',
                      `category` char(127) DEFAULT NULL,
                      `requisites_print` text COMMENT 'Наименование для печати',
                      `url` char(127) DEFAULT NULL,
                      `status` char(100) DEFAULT NULL,
                      `vat` decimal(11,2) DEFAULT NULL COMMENT 'ставка налога',
                      PRIMARY KEY (`id`),
                      KEY `import_1c_category` (`import_1c_category`),
                      KEY `1c` (`id1c`),
                      KEY `import_1c_brend` (`import_1c_brend`),
                      KEY `status` (`status`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='сам каталог'");
                $this->addSql("CREATE TABLE `import_1c_tovar_properties` (
                      `1c_tovar_id1c` char(127) DEFAULT NULL,
                      `property_list_id` char(127) DEFAULT NULL COMMENT 'ID значения характристики как в 1С',
                      `property_id` char(127) DEFAULT NULL COMMENT 'ID характеристики',
                      `value` char(255) DEFAULT NULL COMMENT 'значение характеристики',
                      KEY `1c_tovar_id1c` (`1c_tovar_id1c`),
                      KEY `property_list_id` (`property_list_id`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='характристики товара'");
                break;
            }
            default:{
                throw new \Exception("the database {$this->db_type} is not supported !");
            }
        }
    }

    public function down($schema, $adapter)
    {
        switch ($this->db_type){
            case "mysql":{
                $this->addSql("DROP TABLE IF EXISTS `import_1c_brend`");
                $this->addSql("DROP TABLE IF EXISTS `import_1c_category`");
                $this->addSql("DROP TABLE IF EXISTS `import_1c_file`");
                $this->addSql("DROP TABLE IF EXISTS `import_1c_price`");
                $this->addSql("DROP TABLE IF EXISTS `import_1c_price_type`");
                $this->addSql("DROP TABLE IF EXISTS `import_1c_properties`");
                $this->addSql("DROP TABLE IF EXISTS `import_1c_properties_list`");
                $this->addSql("DROP TABLE IF EXISTS `import_1c_requisites`");
                $this->addSql("DROP TABLE IF EXISTS `import_1c_scheme`");
                $this->addSql("DROP TABLE IF EXISTS `import_1c_store`");
                $this->addSql("DROP TABLE IF EXISTS `import_1c_store_type`");
                $this->addSql("DROP TABLE IF EXISTS `import_1c_tovar`");
                $this->addSql("DROP TABLE IF EXISTS `import_1c_tovar_properties`");
                break;
            }
            default:{
                throw new \Exception("the database {$this->db_type} is not supported !");
            }
        }
    }
}
