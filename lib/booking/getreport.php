<?php

namespace Webpractik\Main\Booking;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\UserTable;
use Webpractik\Api\Response;
use Webpractik\Main\Orm\AddServicesTable;
use Webpractik\Main\Orm\StatusTable;

class GetReport extends Response
{
    const BIRTHDAY       = 1;
    const ANIMATOR       = 2;
    const PHOTOGRAF      = 3;
    const LOUNGE         = 4;
    const PROP_SOURCE_ID = 47; //источник
    const PROP_REASON_ID = 128; //основание для скидки
    const FULLPRICE_CODE = 88005553535;
    
    private $arAddServices = [];
    private $arSourceValue = [];
    private $arStatusValue = [];
    private $arSaleReason  = [];
    private $arManager     = [];
    
    public $questId = 0;
    public $status  = [];
    public $arDate  = [];
    
    /**
     * GetReport constructor.
     * @param $arParams
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function __construct($arParams)
    {
        parent::__construct($arParams);
        $this->arManager     = $this->getManagerList();
        $this->arSourceValue = $this->getListProp(self::PROP_SOURCE_ID);
        $this->arSaleReason  = $this->getListProp(self::PROP_REASON_ID);
        $this->arStatusValue = $this->getStatusList();
        $this->arAddServices = $this->getAdditionalServices();
    }
    
    
    public function handler()
    {
        try {
            $this->setParams();
            $this->response->addParam('getreport', $this->getReport($this->questId, $this->status, $this->arDate))->sendSuccess();
            
        } catch (ArgumentException $e) {
            $this->response
                ->addParam('errorCode', $e->getParameter())
                ->addParam('errorText', $e->getMessage())
                ->send();
        }
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
        $arSources = [
            [
                'ID'    => 0,
                'VALUE' => 'Не выбрано',
            ],
        ];
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
    
    /**
     * получение данных для страницы отчетов
     *
     * @param int   $questId
     * @param array $arActive
     * @param array $arDate
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getReport(int $questId, array $arActive, array $arDate = []): array
    {
        $query = \Webpractik\Main\Orm\SheduleTable::query()
            ->setSelect(['*'])
            ->addSelect('SOURCE.UTS_ID', 'SOURCE_ID')
            ->addSelect('STATUS.NAME', 'STATUS_VAL')
            ->addSelect('STATUS.LEVEL', 'STATUS_LVL')
            ->addSelect('CERTIFICATE', 'C')
            ->addSelect('ADD_SERVICE')
            ->addSelect('PROMOCODE.PROMOCODE', 'PROMO_VAL')
            ->where('QUEST_ID', $questId)
            ->addOrder('DATE_RESERVE');
        
        //фильтр по активности
        $query->where('ACTIVE', 1);
        
        //фильтр по статусу
        $statusFilter = Query::filter()
            ->logic('or');
        if (in_array(0, $arActive)) {
            $statusFilter->whereIn('STATUS_ID', [10, 11]);
        }
        if (in_array(1, $arActive)) {
            $statusFilter->whereNotIn('STATUS_ID', [10, 11]);
        }
        $query->where($statusFilter);
        
        //фильтр по дате
        $dateFilter = Query::filter()
            ->logic('or');
        if (count($arDate) > 0) {
            foreach ($arDate as $key => $date) {
                $dateFilter->whereBetween('DATE_RESERVE', new DateTime($date . '00:00'), new DateTime($date . '23:59'));
            }
        }
        $query->where($dateFilter);
        
        $dbRes   = $query->exec();
        $arItems = [];
        while ($booking = $dbRes->fetch()) {
            $tmpStatuses = $this->arStatusValue;
            foreach ($tmpStatuses as $key => &$arStatus) {
                if (in_array($booking['STATUS_ID'], [10, 11])) {
                    $arStatus['ACTIVE'] = false;
                    continue;
                }
                if ($arStatus['LEVEL'] > (int)$booking['STATUS_LVL'] || $arStatus['ID'] == (int)$booking['STATUS_ID']) {
                    $arStatus['ACTIVE'] = true;
                } else {
                    $arStatus['ACTIVE'] = false;
                }
            }
            
            $tmpStatuses = array_values($tmpStatuses);
            
            $arSources    = $this->setSelected($this->arSourceValue, (int)$booking['SOURCE_ID']);
            $arSaleReason = $this->setSelected($this->arSaleReason, (int)$booking['REASON_ID']);
            $arManagers   = $this->setSelected($this->arManager, (int)$booking['MANAGER_ID']);
            $arStatuses   = $this->setSelected($tmpStatuses, (int)$booking['STATUS_ID']);
            
            $arSelectedServices = unserialize($booking['ADD_SERVICE']);
            $arServices         = [];
            foreach ($this->arAddServices as $key => $arAddService) {
                $arServices[$key] = [
                    'id'         => $arAddService['SERVICE_ID'],
                    'field_name' => $arAddService['SERVICE_NAME'],
                    'name'       => $arAddService['PRICE'],
                ];
                if (in_array($arAddService['SERVICE_ID'], $arSelectedServices)) {
                    $arServices[$key]['selected'] = true;
                }
            }
            
            //цены и скидки
            $arPrices = $this->execCalculator($booking, $arServices);
            
            /** @var \Bitrix\Main\Type\Date $date */
            $date = $booking['DATE_RESERVE'];
            
            $arItems [] = [
                'id'               => $booking['BOOKING_ID'],
                'quest_id'         => $booking['QUEST_ID'],
                'date'             => $date->format('d.m.Y'),
                'time'             => $date->format('G:i'),
                'players_cnt'      => $booking['PLAYERS_CNT'],
                'questPrice'       => $arPrices['BASE_PRICE'],
                'promocode'        => $booking['PROMO_VAL'],
                'certificate'      => [
                    'id'    => (int)$booking['CCERT_NUM'],
                    'value' => $arPrices['CERT_VALUE'],
                ],
                'discount'         => $arPrices['DISCOUNT'],
                'discount_type'    => $this->convertDiscountType($arPrices['DISCOUNT_TYPE']),
                'reasonId'         => $arPrices['DISCOUNT_REASON_ID'],
                'saleReasonCustom' => $booking['REASON_OTHER'],
                'totalPrice'       => $arPrices['TOTAL_PRICE'],
                'loyaltyCard'      => $booking['LOYAL_CARD'],
                'phone'            => $booking['CLIENT_PHONE'],
                'email'            => $booking['CLIENT_EMAIL'],
                'clientName'       => $booking['CLIENT_NAME'],
                'utm'              => $arSources,
                'manager'          => $arManagers,
                'status'           => $arStatuses,
                'additional'       => $arServices,
                'saleReason'       => $arSaleReason,
                'comment'          => $booking['COMMENT'],
                'maxPlayers'       => $this->getMaxPlayers(),
            ];
        }
        if (count($arItems) < 1) {
            throw new ArgumentException('По запросу ничего не найдено', 'EMPTY_RESULT');
        }
        return $arItems;
    }
    
    /**
     * @param array $booking
     * @param array $arServices
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function execCalculator(array $booking, array $arServices): array
    {
        $calculator = new BookingCalculator($booking['DATE_RESERVE']->format('d.m.Y G:i'), $booking['PLAYERS_CNT'], $booking['QUEST_ID']);
        $calculator->setThrowExeption(false);
        foreach ($arServices as $arService) {
            if ($arService['selected']) {
                switch ($arService['id']) {
                    case self::BIRTHDAY :
                        $calculator->addBirthday();
                        break;
                    case self::ANIMATOR :
                        $calculator->addAnimator();
                        break;
                    case self::PHOTOGRAF :
                        $calculator->addPhotograf();
                        break;
                    case self::LOUNGE :
                        $calculator->addLounge();
                        break;
                }
            }
        }
        if ($booking['CLIENT_PHONE']) {
            $calculator->setPhone($booking['CLIENT_PHONE']);
        }
        if ($booking['LOYAL_CARD']) {
            $calculator->setLoyaltyCard($booking['LOYAL_CARD']);
        }
        if (strlen($booking['PROMO_VAL']) > 0) {
            $calculator->setPromocode($booking['PROMO_VAL']);
        }
        if (strlen($booking['CCERT_NUM']) > 0) {
            $calculator->setCetrificate($booking['CCERT_NUM']);
            
        }
        
        $arPrices = $calculator->calculate();
        
        $arPrices['CERT_VALUE'] = '';
        if (strlen($booking['CCERT_NUM']) > 2) {
            $arPrices['CERT_VALUE'] = $calculator->getDiscountByCetrificate($booking['CCERT_NUM']);
            if ($arPrices['CERT_VALUE']['NOMINAL'] == false) {
                $arPrices['CERT_VALUE'] = $arPrices['BASE_PRICE'];
            } else {
                $arPrices['CERT_VALUE'] = $arPrices['CERT_VALUE']['DISCOUNT'];
            }
        }
        
        return $arPrices;
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
        $userReference = new ReferenceField(
            'USER',
            UserTable::class,
            Query\Join::on('this.USER_ID', 'ref.ID'));
        $dbres         = UserGroupTable::query()
            ->registerRuntimeField($userReference)
            ->addSelect('USER_ID', 'ID')
            ->addSelect(Query::expr()->concat(['USER.NAME', 'USER.LAST_NAME']), 'VALUE')
            ->whereIn('GROUP_ID', [1, 6, 7])
            ->exec();
        $users         = $dbres->fetchAll();
        if (count($users) > 0) {
            $result = $users;
        }
        
        return $result;
    }
    
    /**
     * Устанавливает свойству параметер selected = true, если оно выбрано
     *
     * @param array $arPropValues
     * @param int   $id
     * @return array
     */
    private function setSelected(array $arPropValues, int $id): array
    {
        $arValues = [];
        foreach ($arPropValues as $key => $arValue) {
            $arValues[$key] = [
                'id'   => $arValue['ID'],
                'name' => $arValue['VALUE'],
            ];
            if ($arValue['ID'] == $id) {
                $arValues[$key]['selected'] = true;
            }
            if ($arValue['ACTIVE'] == true) {
                $arValues[$key]['active'] = true;
            } else {
                $arValues[$key]['active'] = false;
            }
            
        }
        return $arValues;
    }
    
    /**
     * получить список доп услуг
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getAdditionalServices(): array
    {
        $result = [];
        $dbRes  = AddServicesTable::query()
            ->setSelect(['*'])
            ->exec();
        while ($service = $dbRes->fetch()) {
            $result[] = $service;
        }
        return $result;
    }
    
    /**
     * получить кол-во игроков
     *
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getMaxPlayers(): int
    {
        $sheduleManager = new SheduleManager();
        return $sheduleManager->getMaxPlayerCnt($this->questId);
    }
    
    /**
     * Получить список статусов
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getStatusList(): array
    {
        $result = [];
        $dbRes  = StatusTable::query()
            ->setSelect(['ID', 'NAME', 'LEVEL'])
            ->exec();
        while ($arField = $dbRes->fetch()) {
            $result[] = [
                'ID'    => $arField['ID'],
                'VALUE' => $arField['NAME'],
                'LEVEL' => $arField['LEVEL'],
            ];
        }
        return $result;
    }
    
    /**
     * Перевод типа скидки из значений rub/percent в 1/0 для передачи в json
     *
     * @param string $type
     * @return int
     */
    private function convertDiscountType(string $type): int
    {
        switch ($type) {
            case 'rub':
                return 1;
                break;
            case 'percent':
                return 0;
                break;
            default:
                return 0;
        }
    }
    
    private function setParams()
    {
        $this->questId = (int)$this->request->get('questId');
        if ($this->questId < 1) {
            throw new ArgumentException('Неверный Id', 'FAIL_QUEST_ID');
        }
        
        $this->arDate = json_decode($this->request->get('date'));
        if (count($this->arDate) < 1) {
            throw new ArgumentException('Не передана дата', 'FAIL_DATE');
        }
        $arStatus = json_decode($this->request->get('status'));
        if (!in_array('activated', $arStatus) && !in_array('deactivated', $arStatus)) {
            throw new ArgumentException('Неверный статус', 'FAIL_STATUS');
        }
        if (in_array('activated', $arStatus)) {
            $this->status[] = 1;
        }
        if (in_array('deactivated', $arStatus)) {
            $this->status[] = 0;
        }
    }
}
