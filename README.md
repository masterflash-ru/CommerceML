# CommerceML

Модуль для Zend Framework 3, для системы Simba, от студии Мастер Флеш.

установка

composer require masterflash-ru/commerceml

Представляет собой завершенный модуль для загрузки каталога на сайт из 1С. Результат работы - заполненные таблицы MySql и файлы для товара в папке data/1c.
После загрузки уже другие модули сайта работают с этими данными по своему усмотрению. Вся работа построена на общих событиях с идентификатором simba.1c:

catalogTruncate - вызывается когда производится полная загрузка каталога, очищается все хранилище и временные таблицы

catalogImport - вызывается когда производится импорт раздела Import.xml - собственно сам каталог

catalogOffers - аналогично для раздела Offers.xml

catalogImportComplete - вызывается после выполнения всех операция по загрузке во временное хранилище

в первых 3-х передается имя файла для обработки с полным путем к нему, приоритет по умолчанию 100.

Можно отключить стандартную обработку, например, когда 1С формирует вообще не стандартный файл. Для обработки вам нужно добавить слушателя для этих событий с идентификатором simba.1c.
Нужно в конфиге установить "standartParser"=>false - в этом случае не устанавливаются обработчики файлов 1С данного модуля

Если необходимо что-то сделать в процессе обработки добавьте нового слушателя для указанных событий, они будут вызваны автоматически с передачей параметров.

Поддерживается полная и частичое обновление каталога, но есть особенности:
1 - таблица import_1c_category очищается только при полной загрузке каталога, т.к. дерево на сайте строится на основе числовых идентификаторов, к которым привязывается товар.
2 - если есть изменения нащвания или появление нового раздела(ов) таблица соответсвенно меняется/дополняется.
3 - связей между таблицами нет, все загружается как есть в файле 1С
4 - все остальные данные, таблицы хранилища, перед загрузкой всегда очищаются, и содержат только обновленную информацию которая есть в текущем файле 1С
5 - товар связывается с import_1c_category на основе идентификаторов 1С.

Модуль каталога должен сам разруливать связи между типами цен, типами склада, файлами-фото, производителями. 
Все эти связи строятся на основе идетификаторов 1С, строк типа 1b2a698c-7e15-11e5-b4e6-8c89a5120b22