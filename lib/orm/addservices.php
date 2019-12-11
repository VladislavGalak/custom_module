<?php
namespace Webpractik\Main\Orm;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ServicesTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_NAME string optional
 * <li> UF_PRICE int optional
 * </ul>
 *
 * @package Bitrix\Services
 **/

class AddServicesTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'add_services';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'SERVICE_ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'column_name' => 'ID',
            ),
            'SERVICE_NAME' => array(
                'data_type' => 'text',
                'column_name' => 'UF_NAME',
                'title' => Loc::getMessage('SERVICES_ENTITY_UF_NAME_FIELD'),
            ),
            'PRICE' => array(
                'data_type' => 'integer',
                'column_name' => 'UF_PRICE',
                'title' => Loc::getMessage('SERVICES_ENTITY_UF_PRICE_FIELD'),
            ),
        );
    }
}