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
 * <li> UF_DESCRIPTION string optional
 * <li> UF_FULL_DESCRIPTION string optional
 * <li> UF_XML_ID string optional
 * </ul>
 *
 * @package Bitrix\Type
 **/

class QuestTypeTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'quest_type';
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
            'DESCRIPTION' => array(
                'data_type' => 'text',
                'column_name' => 'UF_DESCRIPTION',
                'title' => Loc::getMessage('TYPE_ENTITY_UF_DESCRIPTION_FIELD'),
            ),
            'FULL_DESCRIPTION' => array(
                'data_type' => 'text',
                'column_name' => 'UF_FULL_DESCRIPTION',
                'title' => Loc::getMessage('TYPE_ENTITY_UF_FULL_DESCRIPTION_FIELD'),
            ),
            'XML_ID' => array(
                'data_type' => 'text',
                'column_name' => 'UF_XML_ID',
                'title' => Loc::getMessage('TYPE_ENTITY_UF_XML_ID_FIELD'),
            ),
        );
    }
}
