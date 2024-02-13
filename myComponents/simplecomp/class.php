<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

use Bitrix\Main\Loader;

class CIblocList extends CBitrixComponent
{
    public function executeComponent()
    {
        try {
            $this->checkModules();
            $this->getResult();
        } catch (SystemException $e) {
            ShowError($e->getMessage());
        }
    }

    public function onIncludeComponentLang()
    {
        Loc::loadMessages(__FILE__);
    }

    // проверяем установку модуля «Информационные блоки» (метод подключается внутри класса try...catch)
    protected function checkModules()
    {
        // если модуль не подключен
        if (!Loader::includeModule('iblock'))
            // выводим сообщение в catch
            throw new SystemException(Loc::getMessage('SIMPLECOMP_IBLOCK_MODULE_NONE'));
    }

    // обработка массива $arParams (метод подключается автоматически)
    public function onPrepareComponentParams($arParams)
    {
        // время кеширования
        if (!isset($arParams['CACHE_TIME'])) {
            $arParams['CACHE_TIME'] = 36000000;
        } else {
            $arParams['CACHE_TIME'] = intval($arParams['CACHE_TIME']);
        }

        $arParams['IBLOCK_ID'] = trim($arParams["IBLOCK_ID"] ?? '');

        // возвращаем в метод новый массив $arParams
        return $arParams;
    }

    // подготовка массива $arResult (метод подключается внутри класса try...catch)
    protected function getResult()
    {
        global $CACHE_MANAGER;
        
        if ($this->StartResultCache()){
            $CACHE_MANAGER->RegisterTag('iblock_id_'.$this->arParams['IBLOCK_ID']);

            //Получаем элементы
            $dbProducts = \Bitrix\Iblock\ElementTable::getList([
                'select' => ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'IBLOCK_ID'],
                'filter' => ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'ACTIVE'=> 'Y'],
            ]);
            while ($arProduct = $dbProducts->fetch()){
                $arIdSections[$arProduct['IBLOCK_SECTION_ID']] = $arProduct['IBLOCK_SECTION_ID'];
                $arIdProduct[$arProduct['ID']] = $arProduct['ID'];
                $arProducts[$arProduct['ID']] = $arProduct;
            }

            //получаем свойства
            $dbProperties = \Bitrix\Iblock\PropertyTable::getList([
                //'select' => array('ID', 'NAME', 'CODE'),
                'select' => array('*'),
                'filter' => array('IBLOCK_ID' => $this->arParams['IBLOCK_ID'])
            ]);
            while ($arProperty = $dbProperties->fetch()){
                $arProperties[$arProperty['CODE']] = $arProperty;
                foreach ($arProducts as $keyResItem => $item){
                    $arProducts[$keyResItem]['PROPERTIES'][$arProperty['CODE']] = $arProperty;
                }
            }

            //получаем значения свойств
            $dbEnums = \Bitrix\Iblock\ElementPropertyTable::getList([
                'select' => ['*'],
                'filter' => ['IBLOCK_ELEMENT_ID'=> [12,11]]
            ]);
            while ($arEnum = $dbEnums->fetch()){
                foreach ($arProperties as $keyProp => $property){
                    if ($property['ID'] == $arEnum['IBLOCK_PROPERTY_ID']){
                        $arEnum['CODE'] = $property['CODE'];
                        $arPropertiesWithCode[] = $arEnum;
                    }
                }
            }

            //Заполняем $arProducts значением свойств
            foreach ($arPropertiesWithCode as $property){
                if ($arProducts[$property['IBLOCK_ELEMENT_ID']]['PROPERTIES'][$property['CODE']]['MULTIPLE'] == 'Y'){

                    $arProducts[$property['IBLOCK_ELEMENT_ID']]['PROPERTIES'][$property['CODE']]['PROP_VALUES'][] = $property;
                }else{

                    $arProducts[$property['IBLOCK_ELEMENT_ID']]['PROPERTIES'][$property['CODE']]['PROP_VALUE'] = $property;
                }
            }

            //Получаем разделы и заполняем arResult
            $dbSections = \Bitrix\Iblock\SectionTable::getList([
                'select' => ['ID', 'NAME'],
                'filter' => ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'ACTIVE'=> 'Y', 'ID' => $arIdSections],
            ]);
            while ($arSection = $dbSections->fetch()){
                $this->arResult['ITEMS'][$arSection['ID']] = $arSection;
                foreach ($arProducts as $product){
                    if ($product['IBLOCK_SECTION_ID'] == $arSection['ID']){
                        $this->arResult['ITEMS'][$arSection['ID']]['PRODUCTS'][] = $product;
                    }
                }
            }

            $this->includeComponentTemplate();

        }else{
            $this->AbortResultCache();
        }
    }



}
?>