<?php
namespace Webpractik\Main\Orm;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class PeriodTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_DATE_START datetime optional
 * <li> UF_DATE_END datetime optional
 * <li> UF_NAME string optional
 * <li> UF_PRICE_2 int optional
 * <li> UF_PRICE_3 int optional
 * <li> UF_PRICE_4 int optional
 * <li> UF_PRICE_5 int optional
 * <li> UF_PRICE_6 int optional
 * </ul>
 *
 * @package Bitrix\Price
 **/

class SpecialPeriodTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'special_price_period';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('PERIOD_ENTITY_ID_FIELD'),
            ),
            'DATE_START' => array(
                'data_type' => 'datetime',
                'title' => Loc::getMessage('PERIOD_ENTITY_UF_DATE_START_FIELD'),
                'column_name' => 'UF_DATE_START'
            ),
            'DATE_END' => array(
                'data_type' => 'datetime',
                'title' => Loc::getMessage('PERIOD_ENTITY_UF_DATE_END_FIELD'),
                'column_name' => 'UF_DATE_END'
            ),
            'NAME' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('PERIOD_ENTITY_UF_NAME_FIELD'),
                'column_name' => 'UF_NAME'
            ),
            'PRICE_2' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('PERIOD_ENTITY_UF_PRICE_2_FIELD'),
                'column_name' => 'UF_PRICE_2'
            ),
            'PRICE_3' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('PERIOD_ENTITY_UF_PRICE_3_FIELD'),
                'column_name' => 'UF_PRICE_3'
            ),
            'PRICE_4' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('PERIOD_ENTITY_UF_PRICE_4_FIELD'),
                'column_name' => 'UF_PRICE_4'
            ),
            'PRICE_5' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('PERIOD_ENTITY_UF_PRICE_5_FIELD'),
                'column_name' => 'UF_PRICE_5'
            ),
            'PRICE_6' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('PERIOD_ENTITY_UF_PRICE_6_FIELD'),
                'column_name' => 'UF_PRICE_6'
            ),
            'QUESTS' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('PERIOD_ENTITY_UF_QUESTS_FIELD'),
                'column_name' => 'UF_QUESTS'
            ),
            'LOCK_DISCOUNT' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_LOCK_DISCOUNT',
                'title' => Loc::getMessage('PERIOD_ENTITY_UF_LOCK_DISCOUNT_FIELD'),
            ),
        );
    }
}