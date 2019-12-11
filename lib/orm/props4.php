<?php
namespace Webpractik\Main\Orm;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ElementPropS4Table
 *
 * Fields:
 * <ul>
 * <li> IBLOCK_ELEMENT_ID int mandatory
 * <li> PROPERTY_4 string optional
 * <li> PROPERTY_6 int optional
 * <li> PROPERTY_7 string optional
 * <li> PROPERTY_8 string optional
 * <li> PROPERTY_9 int optional
 * <li> PROPERTY_10 string optional
 * <li> PROPERTY_11 string optional
 * <li> PROPERTY_12 int optional
 * <li> PROPERTY_13 string optional
 * <li> PROPERTY_14 string optional
 * <li> PROPERTY_15 int optional
 * <li> PROPERTY_16 string optional
 * <li> PROPERTY_19 string optional
 * <li> PROPERTY_20 int optional
 * <li> PROPERTY_28 string optional
 * </ul>
 *
 * @package Bitrix\Iblock
 **/

class Props4Table extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_iblock_element_prop_s4';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'IBLOCK_ELEMENT_ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_IBLOCK_ELEMENT_ID_FIELD'),
            ),
            'PROPERTY_4' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_4_FIELD'),
            ),
            'PROPERTY_6' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_6_FIELD'),
            ),
            'PROPERTY_7' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_7_FIELD'),
            ),
            'PROPERTY_8' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_8_FIELD'),
            ),
            'PROPERTY_9' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_9_FIELD'),
            ),
            'PROPERTY_10' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_10_FIELD'),
            ),
            'PROPERTY_11' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_11_FIELD'),
            ),
            'PROPERTY_12' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_12_FIELD'),
            ),
            'PROPERTY_13' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_13_FIELD'),
            ),
            'PROPERTY_14' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_14_FIELD'),
            ),
            'PROPERTY_15' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_15_FIELD'),
            ),
            'PROPERTY_16' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_16_FIELD'),
            ),
            'PROPERTY_19' => array(
                'data_type' => 'text',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_19_FIELD'),
            ),
            'PROPERTY_20' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_20_FIELD'),
            ),
            'PROPERTY_32' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_32_FIELD'),
            ),
            'PROPERTY_33' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_33_FIELD'),
            ),
            'QUEST_TYPE_ID' => array(
                'data_type' => 'text',
                'column_name' => 'PROPERTY_28',
                'title' => Loc::getMessage('ELEMENT_PROP_S4_ENTITY_PROPERTY_28_FIELD'),
            ),
            
        );
    }
}
