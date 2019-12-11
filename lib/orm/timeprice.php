<?php

namespace Webpractik\Main\Orm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Main\Localization\Loc;


Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class PriceTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_AVAILABLE int optional
 * <li> UF_PRICE int optional
 * <li> UF_DAY int optional
 * <li> UF_TIME int optional
 * </ul>
 *
 * @package Bitrix\Price
 **/
class TimePriceTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'time_price';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new Entity\IntegerField('PRICE_ID', [
                'primary'      => true,
                'autocomplete' => true,
                'column_name'  => 'ID',
            ]),
            new Entity\IntegerField('QUEST_TYPE', [
                'column_name' => 'UF_QUEST_TYPE',
                'title'       => 'Тип квестов',
            ]),
            new Entity\IntegerField('PERIOD', [
                'column_name' => 'UF_PERIOD',
                'title'       => 'Период времени',
            ]),
            new Entity\IntegerField('PLAYERS_CNT', [
                'column_name' => 'UF_PLAYERS_CNT',
                'title'       => 'Количество игроков',
            ]),
            new Entity\IntegerField('PRICE', [
                'column_name' => 'UF_PRICE',
                'title'       => 'Цена игры',
            ]),
            new Entity\IntegerField('TYPE_PRICE', [
                'column_name' => 'UF_TYPE_PRICE',
                'title'       => 'Тип квеста/цены',
            ]),
    
        ];
    }
}
