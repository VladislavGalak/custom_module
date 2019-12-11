<?php
namespace Webpractik\Main\Orm;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class FieldEnumTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_FIELD_ID int optional
 * <li> VALUE string(255) mandatory
 * <li> DEF bool optional default 'N'
 * <li> SORT int optional default 500
 * <li> XML_ID string(255) mandatory
 * </ul>
 *
 * @package Bitrix\User
 **/

class UtsTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_user_field_enum';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'UTS_ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('FIELD_ENUM_ENTITY_ID_FIELD'),
                'column_name' => 'ID',
            ),
            'USER_FIELD_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('FIELD_ENUM_ENTITY_USER_FIELD_ID_FIELD'),
            ),
            'VALUE' => array(
                'data_type' => 'string',
                'required' => true,
                'validation' => array(__CLASS__, 'validateValue'),
                'title' => Loc::getMessage('FIELD_ENUM_ENTITY_VALUE_FIELD'),
            ),
            'DEF' => array(
                'data_type' => 'boolean',
                'values' => array('N', 'Y'),
                'title' => Loc::getMessage('FIELD_ENUM_ENTITY_DEF_FIELD'),
            ),
            'SORT' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('FIELD_ENUM_ENTITY_SORT_FIELD'),
            ),
            'XML_ID' => array(
                'data_type' => 'string',
                'required' => true,
                'validation' => array(__CLASS__, 'validateXmlId'),
                'title' => Loc::getMessage('FIELD_ENUM_ENTITY_XML_ID_FIELD'),
            ),
        );
    }

    /**
     *  Returns validators for VALUE field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateValue()
    {
        return array(
            new Main\Entity\Validator\Length(null, 255),
        );
    }
}