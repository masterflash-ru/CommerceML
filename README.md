# CommerceML

Модуль для Zend Framework 3, для системы Simba, от студии Мастер Флеш.
Пока не отлажено на реальном сайте!!!

установка:
composer require masterflash-ru/commerceml

Представляет собой завершенный модуль для загрузки каталога на сайт из 1С. Результат работы - заполненные таблицы MySql и файлы для товара в папке data/1c.
Пока не поддерживаются товары с торговыми предложениями (характеристиками)!!!
После загрузки уже другие модули сайта работают с этими данными по своему усмотрению. Вся работа построена на общих событиях с идентификатором simba.1c:

* catalogTruncate - вызывается когда производится полная загрузка каталога, очищается все хранилище и временные таблицы
* catalogImport - вызывается когда производится импорт раздела Import.xml - собственно сам каталог, по умолчанию вызывается внутренний стандартный парсер
* catalogOffers - аналогично для раздела Offers.xml, аналогично вызывается стандартный парсер
* catalogImportComplete - вызывается после выполнения операций по загрузке во временное хранилище файлов типа import, по этому событию сторонние модули могут работать с данными из таблиц.
* catalogOffersComplete - вызывается после выполнения операций по загрузке во временное хранилище файлов типа offers, по этому событию сторонние модули могут работать с данными из таблиц.

в первых 3-х передается имя файла для обработки с полным путем к нему, приоритет по умолчанию 100.

Можно отключить стандартную обработку, например, когда 1С формирует вообще не стандартный файл. Для обработки вам нужно добавить слушателя для этих событий с идентификатором simba.1c.
Нужно в конфиге установить "standartParser"=>false - в этом случае не устанавливаются обработчики файлов 1С данного модуля

Если необходимо что-то сделать в процессе обработки добавьте нового слушателя для указанных событий, они будут вызваны автоматически с передачей параметров.

Поддерживается полная и частичое обновление каталога, но есть особенности:

1. таблица import_1c_category очищается только при полной загрузке каталога, т.к. дерево на сайте строится на основе числовых идентификаторов, к которым привязывается товар.
2. если есть изменения названия или появление нового раздела(ов) таблица соответсвенно меняется/дополняется.
3. связей между таблицами нет, все загружается как есть в файле 1С
4. все остальные данные, таблицы хранилища, перед загрузкой всегда очищаются, и содержат только обновленную информацию которая есть в текущем файле 1С
5. товар связывается с import_1c_category на основе идентификаторов 1С.

Модуль каталога должен сам разруливать связи между типами цен, типами склада, файлами-фото, производителями. 
Все эти связи строятся на основе идетификаторов 1С, строк типа 1b2a698c-7e15-11e5-b4e6-8c89a5120b22

Модуль не предоставляет никакое API, он только обрабатывает протокол обмена и имеет стандартный парсер-обработчик и все. Результат работы хранится в комплекте таблиц MySql.
в конфиге имеется секция "1c", которая вообще касается 1С
```php

    "1c"=>[
        //логин/пароль для базовой аутентификации 1С
        "login"=>[
            "admin"=>"********",
            ],
        "temp1c"=>__DIR__."/../../../../data/1c/",
        "standartParser"=>true,
    ],
```
Таблицы:
* import_1c_brend - производители, сразу генерируется URL для возможного перехода к описанию производителя
* import_1c_category - структура категорий, флаг flag_change=1, тогда были изменения в этой структуре (данной строки), связи между узлами через числовые идентификаторы
* import_1c_file - сопутствующие файлы для товара
* import_1c_price - цены товара по типам
* import_1c_price_type - типы цен, flag_change=1 - новый тип, flag_change=2 - изменения
* import_1c_properties - справочник характеристик, type: str, voc, соотвественно просто строка или список
* import_1c_properties_list - список вариантов характеристик
* import_1c_scheme - информация о схеме обмена, информация берется из заголовков файла обмена (версия, дата обмена)
* import_1c_sklad - складская информация о товаре (остаток по складам)
* import_1c_sklad_type - доступные склады, внутри flag_change=1 - новый тип, flag_change=2 - изменения
* import_1c_tovar - собственно информация товарной позиции
* import_1c_tovar_properties - характристики товара, если тип хар-ки это список, привязка с списку
* import_1c_requisites - Дополнительные реквизиты товара, хранит имя параметра=>значение параметра


