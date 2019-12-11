<?php

namespace Webpractik\Main\Orm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Bitrix\Main\Entity\Query\Join;


Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class Table
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_CREATOR double optional
 * <li> UF_DATE_CREATE datetime optional
 * <li> UF_DATE_UPDATE datetime optional
 * <li> UF_RESERVE_DATE date optional
 * <li> UF_PRICE_ID int optional
 * <li> UF_PLAYERS_CNT int optional
 * <li> UF_PROMOCODE int optional
 * <li> UF_CERTIFICATE int optional
 * <li> UF_LOYAL_CARD int optional
 * <li> UF_CLIENT_NAME string optional
 * <li> UF_CLIENT_PHONE string optional
 * <li> UF_EMAIL string optional
 * <li> UF_COMMENT string optional
 * <li> UF_UTM int optional
 * <li> UF_STATUS int optional
 * <li> UF_MANAGER_ID int optional
 * </ul>
 *
 * @package Bitrix\
 **/
class SheduleTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'shedule';
    }
    
    /**
     *Returns entity map definition.
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap()
    {
        return [
            new Entity\IntegerField('BOOKING_ID', [
                'primary'      => true,
                'autocomplete' => true,
                'column_name'  => 'ID',
            
            ]),
            new Entity\FloatField('CREATED_BY', [
                'column_name' => 'UF_CREATOR',
            ]),
            new Entity\DatetimeField('DATE_CREATE', [
                'column_name'   => 'UF_DATE_CREATE',
                'default_value' => new Type\DateTime,
            ]),
            new Entity\DatetimeField('DATE_UPDATE', [
                'column_name'   => 'UF_DATE_UPDATE',
                'default_value' => new Type\DateTime,
            ]),
            new Entity\DatetimeField('DATE_RESERVE', [
                'column_name'   => 'UF_DATE_RESERVE',
                'default_value' => new Type\DateTime,
            ]),
            new Entity\IntegerField('ACTIVE', [
                'column_name' => 'UF_ACTIVE',
            ]),
            new Entity\IntegerField('QUEST_ID', [
                'column_name' => 'UF_QUEST',
            ]),
            new Entity\IntegerField('PLAYERS_CNT', [
                'column_name' => 'UF_PLAYERS_CNT',
            ]),
            new Entity\IntegerField('PROMOCODE_ID', [
                'column_name' => 'UF_PROMOCODE',
            ]),
            new Entity\IntegerField('ADD_SERVICE', [
                'column_name' => 'UF_ADDITIONAL',
            ]),
            new Entity\IntegerField('CERTIFICATE_ID', [
                'column_name' => 'UF_CERTIFICATE',
            ]),
            new Entity\IntegerField('LOYAL_CARD', [
                'column_name' => 'UF_LOYAL_CARD',
            ]),
            new Entity\TextField('CLIENT_NAME', [
                'column_name' => 'UF_CLIENT_NAME',
                'title'       => 'Клиент',
            ]),
            new Entity\TextField('CLIENT_PHONE', [
                'column_name' => 'UF_CLIENT_PHONE',
                'title'       => 'Телефон',
            ]),
            new Entity\TextField('CLIENT_EMAIL', [
                'column_name' => 'UF_EMAIL',
                'title'       => 'Email',
            ]),
            new Entity\IntegerField('REASON_ID', [
                'column_name' => 'UF_SALE_REASON',
            ]),
            new Entity\TextField('REASON_OTHER', [
                'column_name' => 'UF_SALE_REASON_OTHER',
                'title'       => 'reason other',
            ]),
            new Entity\TextField('COMMENT', [
                'column_name' => 'UF_COMMENT',
                'title'       => 'Комментарий',
            ]),
            new Entity\IntegerField('UTM', [
                'column_name' => 'UF_UTM',
            ]),
            new Entity\IntegerField('STATUS_ID', [
                'column_name' => 'UF_STATUS',
            ]),
            new Entity\IntegerField('MANAGER_ID', [
                'column_name' => 'UF_MANAGER_ID',
            ]),
            new Entity\ReferenceField(
                'SALE_REASON',
                \Webpractik\Main\Orm\UtsTable::class,
                Join::on('this.REASON_ID', 'ref.UTS_ID')),
            new Entity\ReferenceField(
                'SOURCE',
                \Webpractik\Main\Orm\UtsTable::class,
                Join::on('this.UTM', 'ref.UTS_ID')),
            new Entity\ReferenceField(
                'STATUS',
                \Webpractik\Main\Orm\StatusTable::class,
                Join::on('this.STATUS_ID', 'ref.ID')),
            new Entity\ReferenceField(
                'CERTIFICATE',
                \Webpractik\Main\Orm\CertificateTable::class,
                Join::on('this.CERTIFICATE_ID', 'ref.CERT_ID')),
            new Entity\ReferenceField('PROMOCODE',
                \Webpractik\Main\Orm\PromocodeTable::class,
                Join::on('this.PROMOCODE_ID', 'ref.PROMO_ID')),
        ];
    }
}
