<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
---</br>

<p><b><?=GetMessage("SIMPLECOMP_CAT_TITLE")?></b></p>
<?if(count($arResult['ITEMS']) > 0){?>
    <ul>
        <?foreach ($arResult['ITEMS'] as $arItem){?>
            <li>
                <b>
                    <?=$arItem['NAME'];?>
                </b>
            </li>

            <?if(count($arItem['PRODUCTS']) > 0){?>
                <ul>
                    <?foreach ($arItem['PRODUCTS'] as $product){?>
                        <li>
                            <?=$product['NAME']?> - (
                                <?if(!empty($product['PROPERTIES']['TAGS']['PROP_VALUES'])){?>
                                    <?foreach ($product['PROPERTIES']['TAGS']['PROP_VALUES'] as $propValue){
                                        $arValues[] = $propValue['VALUE'];
                                    }?>
                                    <?=implode(", ", $arValues)?>
                                    <?unset($arValues)?>
                                <?}?>
                            )
                        </li>
                    <?}?>
                </ul>
            <?}?>

        <?}?>
    </ul>
<?}?>