<?php
namespace Webpractik\Main\Orm;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class TypeTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_NAME string optional
 * <li> UF_VALUE double optional
 * <li> UF_FULL_PRICE int optional
 * </ul>
 *
 * @package Bitrix\Type
 **/

class CertificateTypeTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'certificate_type';
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
                'title' => Loc::getMessage('TYPE_ENTITY_ID_FIELD'),
            ),
            'NAME' => array(
                'data_type' => 'text',
                'column_name' => 'UF_NAME',
                'title' => Loc::getMessage('TYPE_ENTITY_UF_NAME_FIELD'),
            ),
            'VALUE' => array(
                'data_type' => 'float',
                'column_name' => 'UF_VALUE',
                'title' => Loc::getMessage('TYPE_ENTITY_UF_VALUE_FIELD'),
            ),
            'FULL_PRICE' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_FULL_PRICE',
                'title' => Loc::getMessage('TYPE_ENTITY_UF_FULL_PRICE_FIELD'),
            ),
            'ACTIVE' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_ACTIVE',
                'title' => Loc::getMessage('TYPE_ENTITY_UF_ACTIVE_FIELD'),
            ),
        );
    }
}
