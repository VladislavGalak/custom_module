<?php
namespace Webpractik\Main\Orm;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class Table
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_PROMOCODE string optional
 * <li> UF_MECHANIC int optional
 * <li> UF_DISCOUNT_3 int optional
 * <li> UF_DISCOUNT_4 int optional
 * <li> UF_DISCOUNT_5 int optional
 * <li> UF_DISCOUNT_6 int optional
 * <li> UF_DISCOUNT_2 int optional
 * <li> UF_QUESTS string optional
 * <li> UF_ACTIVE_FROM datetime optional
 * <li> UF_ACTIVE_TO datetime optional
 * <li> UF_NAME string optional
 * </ul>
 *
 * @package Bitrix\
 **/

class PromocodeTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'promocode';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'PROMO_ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'column_name' => 'ID',
                'autocomplete' => true,
                'title' => Loc::getMessage('_ENTITY_ID_FIELD'),
            ),
            'PROMOCODE' => array(
                'data_type' => 'text',
                'column_name' => 'UF_PROMOCODE',
                'title' => Loc::getMessage('_ENTITY_UF_PROMOCODE_FIELD'),
            ),
            'MECHANIC' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_MECHANIC',
                'title' => Loc::getMessage('_ENTITY_UF_MECHANIC_FIELD'),
            ),
            'VALUE_2' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_DISCOUNT_2',
                'title' => Loc::getMessage('_ENTITY_UF_DISCOUNT_3_FIELD'),
            ),
            'VALUE_3' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_DISCOUNT_3',
                'title' => Loc::getMessage('_ENTITY_UF_DISCOUNT_3_FIELD'),
            ),
            'VALUE_4' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_DISCOUNT_4',
                'title' => Loc::getMessage('_ENTITY_UF_DISCOUNT_3_FIELD'),
            ),
            'VALUE_5' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_DISCOUNT_5',
                'title' => Loc::getMessage('_ENTITY_UF_DISCOUNT_3_FIELD'),
            ),
            'VALUE_6' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_DISCOUNT_6',
                'title' => Loc::getMessage('_ENTITY_UF_DISCOUNT_3_FIELD'),
            ),
            'QUESTS' => array(
                'data_type' => 'text',
                'column_name' => 'UF_QUESTS',
                'title' => Loc::getMessage('_ENTITY_UF_QUESTS_FIELD'),
            ),
            'ACTIVE_FROM' => array(
                'data_type' => 'datetime',
                'column_name' => 'UF_ACTIVE_FROM',
                'title' => Loc::getMessage('_ENTITY_UF_ACTIVE_FROM_FIELD'),
            ),
            'ACTIVE_TO' => array(
                'data_type' => 'datetime',
                'column_name' => 'UF_ACTIVE_TO',
                'title' => Loc::getMessage('_ENTITY_UF_ACTIVE_TO_FIELD'),
            ),
            'NAME' => array(
                'data_type' => 'text',
                'column_name' => 'UF_NAME',
                'title' => Loc::getMessage('_ENTITY_UF_NAME_FIELD'),
            ),
            'PLASHKA' => array(
                'data_type' => 'text',
                'column_name' => 'UF_ACTION_PLASHKA',
                'title' => Loc::getMessage('_ENTITY_UF_NAME_FIELD'),
            ),
            'TEXT' => array(
                'data_type' => 'text',
                'column_name' => 'UF_ACTION_TEXT',
                'title' => Loc::getMessage('_ENTITY_UF_NAME_FIELD'),
            ),
        );
    }
}
