<?php

namespace Webpractik\Main\Booking;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\UserTable;
use Webpractik\Main\Orm\AddServicesTable;
use Webpractik\Main\Orm\CertificateTable;
use Webpractik\Main\Orm\LoyaltyCardTable;
use Webpractik\Main\Orm\PromocodeTable;
use Webpractik\Main\Orm\SheduleTable;

class BookingManager
{
    const PROP_SOURCE_ID  = 47; //источник
    const PROP_STATUS_ID  = 48; //статус
    const BIRTHDAY        = 1;
    const ANIMATOR        = 2;
    const PHOTOGRAF       = 3;
    const LOUNGE          = 4;
    const CANCELED_STATUS = [10, 11];
    
    private $arAddServices = [];
    private $arManager     = [];
    private $bookingId     = 0;
    private $calculator;
    private $arFields      = [];
    
    public function __construct($questId, $date, $playersCnt)
    {
        if ($this->bookingId == 0) {
            $this->arFields['DATE_RESERVE'] = new DateTime($date);
            $this->arFields['QUEST_ID']     = $questId;
            $this->arFields['PLAYERS_CNT']  = $playersCnt;
            $this->arFields['STATUS_ID']    = 1;//статус новый
        }
        $this->checkDatetime();
        $this->arManager     = $this->getManagerList();
        $this->arAddServices = $this->getAdditionalServices();
        $this->calculator    = new BookingCalculator($date, $playersCnt, $questId);
    }
    
    /**
     * Добавить новую бронь
     *
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function addNew(): bool
    {
        if ($this->isTimeReserved((int)$this->arFields['QUEST_ID'], 'default')) {//если бронь уже существует, не создаем дубль
            throw new  ArgumentException('Время уже зарезервировано', 'FAIL');
        }
        $this->serializeMultiParams();
        $dbRes = SheduleTable::add($this->arFields);
        if ($dbRes->isSuccess()) {
            return true;
        } else {
            throw new  ArgumentException('Ошибка бронирования', 'FAIL');
        }
        
    }
    
    /**
     * Изменить бронь
     *
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function update(): bool
    {
        if ($this->bookingId > 0) {
            if ($this->isTimeReserved((int)$this->arFields['QUEST_ID'], 'default', $this->bookingId)) {//если бронь уже существует, не создаем дубль
                throw new  ArgumentException('Время уже зарезервировано', 'FAIL');
            }
            $this->serializeMultiParams();
            SheduleTable::update($this->bookingId, $this->arFields);
            return true;
        } else {
            throw new  ArgumentException('Ошибка сохранения', 'FAIL');
        }
        
    }
    
    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function checkDatetime()
    {
        $dateFilter = new DateTime($this->arFields['DATE_RESERVE']);
        $dbRes      = SheduleTable::query()
            ->setSelect(['BOOKING_ID'])
            ->where('DATE_RESERVE', $dateFilter)
            ->exec();
        $this->setBookingId((int)$dbRes->fetch()['BOOKING_ID']);
    }
    
    /**
     * Проверить забронировано ли время
     *
     *
     * @param int    $questId
     * @param string $datetime
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function isTimeReserved(int $questId, string $datetime = 'default', int $bookingId = 0): bool
    {
        if ($datetime == 'default') {
            $dateFilterFrom = new DateTime($this->arFields['DATE_RESERVE']->toString());
            $dateFilterTo   = new DateTime($this->arFields['DATE_RESERVE']->toString());
        } else {
            $dateFilterFrom = new DateTime($datetime);
            $dateFilterTo   = new DateTime($datetime);
        }
        
        $dateFilterTo->add('+ 1 hour');
        
        $dbRes           = SheduleTable::query()
            ->setSelect(['BOOKING_ID'])
            ->where('DATE_RESERVE', '>=', $dateFilterFrom)
            ->where('DATE_RESERVE', '<', $dateFilterTo)
            ->where('ACTIVE', 1)
            ->whereNotIn('STATUS_ID', self::CANCELED_STATUS)
            ->where('QUEST_ID', $questId)
            ->exec();
        $resultBookingId = (int)$dbRes->fetch()['BOOKING_ID'];
        if ($bookingId != 0 && $resultBookingId == $bookingId) {
            return false;
        }
        // $this->arFields['DATE_RESERVE']->add('-1 hour');
        if ($resultBookingId > 0) {
            return true;
        } else {
            return false;
        }
        
        
    }
    
    /**
     * Получить Id промокода по его коду
     *
     * @param string $promocode
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getPromoIdByCode(string $promocode): int
    {
        $dbRes = PromocodeTable::query()
            ->setSelect(['PROMO_ID'])
            ->where('PROMOCODE', $promocode)
            ->exec();
        return (int)$dbRes->fetch()['PROMO_ID'];
    }
    
    /**
     * Получить id карты по номеру карты
     *
     * @param string $cardNum
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getCardIdByNum(string $cardNum): int
    {
        if (strlen($cardNum) < 1) {
            return 0;
        }
        
        $dbRes = LoyaltyCardTable::query()
            ->setSelect(['CARD_ID'])
            ->where('CODE', $cardNum)
            ->exec();
        return (int)$dbRes->fetch()['CARD_ID'];
    }
    
    /**
     * Получить id сертификата по номеру
     *
     * @param string $certNum
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getCertIdByNum(string $certNum): int
    {
        $dbRes = CertificateTable::query()
            ->setSelect(['CERT_ID'])
            ->where('CERT_NUM', $certNum)
            ->exec();
        return (int)$dbRes->fetch()['CERT_ID'];
    }
    
    /**
     * получить список доп услуг
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getAdditionalServices(): array
    {
        $result = [];
        $dbRes  = AddServicesTable::query()
            ->setSelect(['*'])
            ->exec();
        while ($service = $dbRes->fetch()) {
            $result[$service['SERVICE_ID']] = $service;
        }
        return $result;
    }
    
    /**
     * Получаем список менеджеров
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getManagerList(): array
    {
        $result        = [];
        $userReference = new  ReferenceField(
            'USER',
            UserTable::class,
            Query\Join::on('this.USER_ID', 'ref.ID'));
        $dbres         = UserGroupTable::query()
            ->registerRuntimeField($userReference)
            ->addSelect('USER_ID')
            ->addSelect(Query::expr()->concat('USER.NAME', 'USER.LAST_NAME'), 'VALUE')
            ->whereIn('GROUP_ID', [1, 6, 7])
            ->exec();
        $users         = $dbres->fetchAll();
        if (count($users) > 0) {
            $result = $users;
        }
        
        return $result;
    }
    
    /**
     * получение значений свойств типа список
     *
     * @param $propId
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getListProp($propId): array
    {
        $arSources = [];
        $dbRes     = \Webpractik\Main\Orm\UtsTable::query()
            ->setSelect(['*'])
            ->where('USER_FIELD_ID', $propId)
            ->exec();
        while ($arField = $dbRes->fetch()) {
            if ($arField['USER_FIELD_ID'] == $propId) {
                $arSources[] = [
                    'ID'    => $arField['UTS_ID'],
                    'VALUE' => $arField['VALUE'],
                ];
            }
        }
        return $arSources;
    }
    
    public function serializeMultiParams()
    {
        $this->arFields['ADD_SERVICE'] = serialize($this->arFields['ADD_SERVICE']);
    }
    
    /**
     * @param int $bookingId
     */
    public function setBookingId(int $bookingId)
    {
        $this->bookingId = $bookingId;
    }
    
    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->arFields['CLIENT_NAME'] = $name;
    }
    
    /**
     * @param string $phone
     */
    public function setPhone(string $phone)
    {
        $this->arFields['CLIENT_PHONE'] = $phone;
    }
    
    /**
     * @param string $email
     */
    public function setEmail(string $email)
    {
        $this->arFields['CLIENT_EMAIL'] = $email;
    }
    
    /**
     * @param string $comment
     */
    public function setComment(string $comment)
    {
        $this->arFields['COMMENT'] = $comment;
    }
    
    /**
     * @param int $serviceId
     */
    public function setAddServices(int $serviceId)
    {
        $this->arFields['ADD_SERVICE'][] = $serviceId;
        // array_unique($this->arFields['ADD_SERVICE']);
    }
    
    /**
     * @param $source
     */
    public function setSource($source)
    {
        $this->arFields['UTM'] = $source;
    }
    
    /**
     * @param int $managerId
     */
    public function setManager(int $managerId)
    {
        $this->arFields['MANAGER_ID'] = $managerId;
    }
    
    /**
     * @param int $statusId
     */
    public function setStatus(int $statusId)
    {
        $this->arFields['STATUS_ID'] = $statusId;
    }
    
    /**
     * @param string $date
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function setDate(string $date)
    {
        if (!$this->isTimeReserved($date)) {
            $this->arFields['DATE_RESERVE'] = $date;
        } else {
            throw new  ArgumentException('Дата не установлена', 'FAIL');
        }
        
        
    }
    
    /**
     * @param string $cnt
     */
    public function setPlayerCnt(string $cnt)
    {
        $this->arFields['PLAYERS_CNT'] = $cnt;
    }
    
    /**
     * @param $questId
     */
    public function setQuestId($questId)
    {
        $this->arFields['QUEST_ID'] = $questId;
    }
    
    /**
     * @param $reasonId
     */
    public function setDiscountReason($reasonId)
    {
        $this->arFields['REASON_ID'] = $reasonId;
    }
    
    /**
     * @param $certNum
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function setCertificate($certNum)
    {
        $this->arFields['CERTIFICATE_ID'] = $this->getCertIdByNum($certNum);
    }
    
    /**
     * @param $cardNum
     */
    public function setLoyaltyCard($cardNum)
    {
        $this->arFields['LOYAL_CARD'] = $cardNum/*$this->getCardIdByNum($cardNum)*/
        ;
    }
    
    /**
     * @param $promocode
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function setPromocode($promocode)
    {
        $this->arFields['PROMOCODE_ID'] = $this->getPromoIdByCode($promocode);
    }
    
    /**
     * @param $reason
     */
    public function setCustomReason($reason)
    {
        $this->arFields['REASON_OTHER'] = $reason;
    }
    
    /**
     * Сделать бронь активной
     */
    public function activate()
    {
        $this->arFields['ACTIVE'] = 1;
    }
    
    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->arFields['STATUS_ID'];
        
    }
    
}
