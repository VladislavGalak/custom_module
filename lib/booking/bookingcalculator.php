<?php

namespace Webpractik\Main\Booking;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Webpractik\Main\Orm\AddServicesTable;
use Webpractik\Main\Orm\CertificateTable;
use Webpractik\Main\Orm\LoyaltyCardTable;
use Webpractik\Main\Orm\PromocodeTable;
use Webpractik\Main\Orm\TimePriceTable;
use Webpractik\Main\Tools;

class BookingCalculator
{
    const REASON_PROMOCODE           = 'PROMOCODE';
    const REASON_CERT                = 'CERTIFICATE';
    const REASON_ACTION              = 'ACTION';
    const REASON_LOYAL_CARD          = 'LOYAL_CARD';
    const REASON_LOYAL_CARD_BY_PHONE = 'LOYAL_CARD_PHONE';
    
    const BIRTHDAY  = 1;
    const ANIMATOR  = 2;
    const PHOTOGRAF = 3;
    const LOUNGE    = 4;
    
    const MECHANIC_PERCENT = 65;
    const MECHANIC_RUB     = 66;
    const MECHANIC_FIX     = 67;
    
    private $questId          = 0;
    private $date             = '';
    private $promocode        = '';
    private $certificateNum   = '';
    private $certificateValue = 0;
    private $playersCnt       = 2;
    private $phone            = '';
    private $loyaltyCard      = '';
    private $photo            = false;
    private $animator         = false;
    private $birthday         = false;
    private $lounge           = false;
    private $throwExeption    = true;
    
    public $questTypes = [];
    
    public $reasonTypes = [
        self::REASON_ACTION              => 101,
        self::REASON_LOYAL_CARD          => 100,
        self::REASON_LOYAL_CARD_BY_PHONE => 100,
        self::REASON_PROMOCODE           => 95,
    ];
    
    public function __construct($date, $playersCnt, $questId)
    {
        $this->questTypes = Tools::getMappingQuestType();
        $this->setDate($date);
        $this->setPlayersCnt($playersCnt);
        $this->setQuestId($questId);
    }
    
    /**
     * Посчитать цену и скидки
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function calculate(): array
    {
        $discount = $this->getDiscount();
        if ($discount['REASON'] != '') {
            $discount['REASON_ID'] = $this->reasonTypes[$discount['REASON']];
        }
        /**костыль, чтоб не искался сертификат с номером 0**/
        if ($this->certificateNum == 0) {
            $this->certificateNum = '';
        }
        
        $certificate = $this->getCertificateDiscount();
        $result      = [
            'BASE_PRICE'              => $this->getBasePrice(),
            'BASE_PRICE_BY_PRESON'    => $this->getBasePriceByPerson(),
            'OPTIMAL_PRICE'           => $this->getOptimalPrice(),
            'OPTIMAL_PRICE_BY_PRESON' => $this->getOptimalPriceByPerson(),
            'DISCOUNT'                => $discount['DISPLAY_VALUE'],
            'DISCOUNT_REASON'         => $discount['REASON'],
            'DISCOUNT_REASON_ID'      => $discount['REASON_ID'],
            'DISCOUNT_VALUE'          => $discount['VALUE'],
            'DISCOUNT_TYPE'           => $discount['CURRENCY'],
            'ADDITIONAL_SERVICES'     => $this->getAddServices(),
            'TOTAL_PRICE'             => $this->getTotalPrice(),
        ];
        if ($certificate['VALUE'] > 0) {
            $result['TOTAL_PRICE']          = $this->getTotalPrice($certificate);
            $result['CERTIFICATE_DISCOUNT'] = $certificate['VALUE'];
        }
        return $result;
    }
    
    /**
     * Поулчить основание для скидки
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDiscountReason(): array
    {
        $result   = [
            'REASON'    => '',
            'REASON_ID' => 0,
        ];
        $discount = $this->getDiscount();
        if ($discount['REASON'] != '') {
            $result['REASON']    = $discount['REASON'];
            $result['REASON_ID'] = $this->reasonTypes[$discount['REASON']];
        }
        return $result;
    }
    
    /**
     * Получить конечную цену
     *
     * @param array $certificate
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getTotalPrice(array $certificate = []): int
    {
        $optimalPrice = $this->getOptimalPrice();
        $totalPrice   = $optimalPrice;
        foreach ($this->getAddServices() as $addService) {
            $totalPrice = $totalPrice + $addService['PRICE'];
        }
        if (count($certificate) > 0) {
            $totalPrice = $totalPrice - $certificate['VALUE'];
        }
        return $totalPrice;
    }
    
    /**
     * Получить список установленных доп услуг
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getAddServices(): array
    {
        $servicesFilter = [0];
        $query          = AddServicesTable::query()
            ->setSelect(['SERVICE_NAME', 'PRICE']);
        if ($this->photo) {
            $servicesFilter[] = self::PHOTOGRAF;
        }
        if ($this->animator) {
            $servicesFilter[] = self::ANIMATOR;
        }
        if ($this->birthday) {
            $servicesFilter[] = self::BIRTHDAY;
        }
        if ($this->lounge) {
            $servicesFilter[] = self::LOUNGE;
        }
        if (count($servicesFilter) > 0) {
            $query->whereIn('SERVICE_ID', $servicesFilter);
        }
        $dbRes = $query->exec();
        return $dbRes->fetchAll();
    }
    
    /**
     * Проверить есть ли в этот день специальные цены
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function checkSpecialPrice()
    {
        $date   = new \Bitrix\Main\Type\Date($this->date);
        $dbRes  = \Webpractik\Main\Orm\SpecialPeriodTable::query()
            ->setSelect(['*'])
            ->where('DATE_START', '<', $date)
            ->where('DATE_END', '>', $date)
            ->addOrder('DATE_START', 'DESC')
            ->exec();
        $result = ['APPLY_SPECPRICE' => false];
        while ($specDay = $dbRes->fetch()) {
            if (in_array($this->questId, unserialize($specDay['QUESTS']))) {
                $result['APPLY_SPECPRICE'] = true;
                $result[2]                 = $specDay['PRICE_2'];
                $result[3]                 = $specDay['PRICE_3'];
                $result[4]                 = $specDay['PRICE_4'];
                $result[5]                 = $specDay['PRICE_5'];
                $result[6]                 = $specDay['PRICE_6'];
            }
        }
        return $result;
    }
    
    /**
     * Возвращает базовую стоимость игры
     *
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getBasePrice(): int
    {
        $specPeriod = $this->checkSpecialPrice();
        if ($specPeriod['APPLY_SPECPRICE']) {
            return $specPeriod[$this->playersCnt];
        }
        $dbres = TimePriceTable::query()
            ->setSelect(['*'])
            ->where('PERIOD', $this->getPeriod($this->date))
            ->where('PLAYERS_CNT', $this->playersCnt)
            // ->where('QUEST_TYPE', $this->questTypes[$this->questId])
            ->where('TYPE_PRICE', $this->questTypes[$this->questId])
            ->exec();
        return (int)$dbres->fetch()['PRICE'];
    }
    
    /**
     * Получить скидку
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDiscount(): array
    {
        $basePrice         = $this->getBasePrice();
        $loyaltyDiscount   = $this->getLoyaltyDiscount();
        $promocodeDiscount = $this->getPromocodeDiscount($basePrice);
        return $this->getOptimalDiscount([$loyaltyDiscount, $promocodeDiscount], $basePrice);
    }
    
    /**
     * Получить максимальную скидку из массива доступных
     *
     * @param array $arDiscounts
     * @param int   $basePrice
     * @return array
     */
    public function getOptimalDiscount(array $arDiscounts, int $basePrice): array
    {
        $resultDiscount = [];
        $maxDiscount    = 0;
        if (count($arDiscounts) > 0) {
            foreach ($arDiscounts as $discount) {
                switch ($discount['CURRENCY']) {
                    case 'percent':
                        $currentDiscount = $basePrice * ((int)$discount['VALUE'] / 100);
                        if ($currentDiscount >= $maxDiscount) {
                            $maxDiscount    = $currentDiscount;
                            $resultDiscount = $discount;
                        }
                        break;
                    case 'rub':
                        $currentDiscount = (int)$discount['VALUE'];
                        if ($currentDiscount >= $maxDiscount) {
                            $maxDiscount    = $currentDiscount;
                            $resultDiscount = $discount;
                        }
                        break;
                }
            }
        }
        return $resultDiscount;
    }
    
    /**
     * Получить скидку по промокоду или акции
     *
     * @param int $basePrice
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getPromocodeDiscount(int $basePrice): array
    {
        $arDiscounts = [];
        if (strlen($this->promocode) > 0) {
            $arDiscounts[] = $this->getDiscoutByPromocode($this->promocode, $basePrice);
        }
        $arDiscounts[] = $this->getActionByDate($basePrice);
        
        return $this->getOptimalDiscount($arDiscounts, $basePrice);
    }
    
    /**
     * Получить скидку действующую в данный период
     *
     * @param int $basePrice
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getActionByDate(int $basePrice): array
    {
        $discount = [
            'VALUE'         => 0,
            'CURRENCY'      => 'rub',
            'REASON'        => '',
            'DISPLAY_VALUE' => '',
        ];
        $dbRes    = PromocodeTable::query()
            ->setSelect(['MECHANIC', 'NAME', 'QUESTS', 'PROMOCODE'])
            ->addSelect('VALUE_' . $this->playersCnt, 'VALUE')
            ->where('ACTIVE_FROM', '<', new Date($this->date))
            ->where('ACTIVE_TO', '>', new Date($this->date))
            ->setOrder('ACTIVE_FROM')
            ->exec();
        $result   = false;
        while ($promo = $dbRes->fetch()) {
            if (in_array($this->questId, unserialize($promo['QUESTS'])) && strlen($promo['PROMOCODE']) == 0) {
                $result = $promo;
                break;
            }
        }
        
        if ($result) {
            switch ($result['MECHANIC']) {
                case self::MECHANIC_PERCENT:
                    if ((int)$result['VALUE'] > 25) {//скидка в % не может превышать 25
                        $result['VALUE'] = 25;
                    }
                    $discount = [
                        'VALUE'         => $result['VALUE'],
                        'CURRENCY'      => 'percent',
                        'REASON'        => self::REASON_ACTION,
                        'DISPLAY_VALUE' => $result['VALUE'] . ' %',
                    ];
                    break;
                case self::MECHANIC_RUB:
                    $discount = [
                        'VALUE'         => $result['VALUE'],
                        'CURRENCY'      => 'rub',
                        'REASON'        => self::REASON_ACTION,
                        'DISPLAY_VALUE' => $result['VALUE'] . ' Р.',
                    ];
                    break;
                case self::MECHANIC_FIX:
                    $discountValue = $basePrice - (int)$result['VALUE'];
                    $discount      = [
                        'VALUE'         => $discountValue,
                        'CURRENCY'      => 'rub',
                        'REASON'        => self::REASON_ACTION,
                        'DISPLAY_VALUE' => $discountValue . ' Р.',
                    ];
                    break;
            }
        }
        
        return $discount;
    }
    
    /**
     * Получить скидку по промокоду
     *
     * @param string $promocode
     * @param int    $basePrice
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDiscoutByPromocode(string $promocode, int $basePrice): array
    {
        $discount = [
            'VALUE'         => 0,
            'CURRENCY'      => 'rub',
            'REASON'        => '',
            'DISPLAY_VALUE' => '',
        ];
        $dbRes    = PromocodeTable::query()
            ->setSelect(['MECHANIC', 'NAME', 'QUESTS'])
            ->addSelect('VALUE_' . $this->playersCnt, 'VALUE')
            ->where('PROMOCODE', $promocode)
            ->where('ACTIVE_FROM', '<', new Date($this->date))
            ->where('ACTIVE_TO', '>', new Date($this->date))
            ->setOrder('ACTIVE_FROM')
            ->exec();
        $result   = false;
        while ($promo = $dbRes->fetch()) {
            if (in_array($this->questId, unserialize($promo['QUESTS']))) {
                $result = $promo;
                break;
            }
        }
        if (!$result && strlen($promocode) > 0 && $this->throwExeption) {
            throw new ArgumentException('Введенный промокод не существует или неактивен', 'FAIL');
        }
        if ($result) {
            switch ($result['MECHANIC']) {
                case self::MECHANIC_PERCENT:
                    if ((int)$result['VALUE'] > 25) {//скидка в % не может превышать 25
                        $result['VALUE'] = 25;
                    }
                    $discount = [
                        'VALUE'         => $result['VALUE'],
                        'CURRENCY'      => 'percent',
                        'REASON'        => self::REASON_PROMOCODE,
                        'DISPLAY_VALUE' => $result['VALUE'] . ' %',
                    ];
                    break;
                case self::MECHANIC_RUB:
                    $discount = [
                        'VALUE'         => $result['VALUE'],
                        'CURRENCY'      => 'rub',
                        'REASON'        => self::REASON_PROMOCODE,
                        'DISPLAY_VALUE' => $result['VALUE'] . ' руб',
                    ];
                    break;
                case self::MECHANIC_FIX:
                    $discountValue = $basePrice - (int)$result['VALUE'];
                    $discount      = [
                        'VALUE'         => $discountValue,
                        'CURRENCY'      => 'rub',
                        'REASON'        => self::REASON_PROMOCODE,
                        'DISPLAY_VALUE' => $discountValue . ' руб',
                    ];
                    break;
            }
        }
        
        return $discount;
    }
    
    /**
     * Получить информацию о скидке по сертификату
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getCertificateDiscount(): array
    {
        $discount = [
            'VALUE'         => 0,
            'CURRENCY'      => 'rub',
            'REASON'        => '',
            'DISPLAY_VALUE' => '',
        ];
        
        if ($this->certificateValue > 0) {
            $discount = [
                'VALUE'         => $this->certificateValue,
                'CURRENCY'      => 'rub',
                'REASON'        => self::REASON_CERT,
                'DISPLAY_VALUE' => $this->certificateValue . ' Р.',
            ];
        } elseif (strlen($this->certificateNum) > 0) {
            $certDiscount = $this->getDiscountByCetrificate($this->certificateNum);
            if (!$certDiscount['NOMINAL']) {
                $optimalPrice             = $this->getOptimalPrice();
                $certDiscount['DISCOUNT'] = $optimalPrice;
            }
            
            $discount = [
                'VALUE'         => (int)$certDiscount['DISCOUNT'],
                'CURRENCY'      => 'rub',
                'REASON'        => self::REASON_CERT,
                'DISPLAY_VALUE' => $certDiscount['DISCOUNT'] . ' Р.',
            ];
        }
        return $discount;
    }
    
    /**
     * Скидка по картам лояльности
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getLoyaltyDiscount(): array
    {
        $discount = [
            'VALUE'         => 0,
            'CURRENCY'      => 'percent',
            'REASON'        => '',
            'DISPLAY_VALUE' => '',
        ];
        if (strlen($this->phone) > 0) {//если установлен номер телефона(более приоритетен чем номер карты)
            $discount['VALUE']         = $this->getDiscountByPhone($this->phone);
            $discount['CURRENCY']      = 'percent';
            $discount['REASON']        = self::REASON_LOYAL_CARD_BY_PHONE;
            $discount['DISPLAY_VALUE'] = $discount['VALUE'] . ' %';
            if ($discount['VALUE'] == 0 && strlen($this->loyaltyCard) >= 1) {//если по номеру телефона не нашлось скидки, и установлен номер телефона
                $discount['VALUE']         = $this->getDicountByCard($this->loyaltyCard);
                $discount['CURRENCY']      = 'percent';
                $discount['REASON']        = self::REASON_LOYAL_CARD;
                $discount['DISPLAY_VALUE'] = $discount['VALUE'] . ' %';
            }
        } elseif (strlen($this->loyaltyCard) >= 1) {//если не установлен номер телефона, но установлена карта
            $discount['VALUE']         = $this->getDicountByCard($this->loyaltyCard);
            $discount['CURRENCY']      = 'rub';
            $discount['REASON']        = self::REASON_LOYAL_CARD;
            $discount['DISPLAY_VALUE'] = $discount['VALUE'] . ' %';
        }
        return $discount;
    }
    
    /**
     * Цена с учетом скидок
     *
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getOptimalPrice(): int
    {
        $basePrice    = $this->getBasePrice();
        $optimalPrice = $basePrice;
        $discount     = $this->getDiscount();
        switch ($discount['CURRENCY']) {
            case 'percent':
                $optimalPrice = $basePrice * ((100 - $discount['VALUE']) / 100);
                break;
            case 'rub':
                $optimalPrice = $basePrice - $discount['VALUE'];
                break;
        }
        return $optimalPrice;
    }
    
    
    /**
     * Вычисляет к какому периоду времени относится заданная дата
     *
     * @param string $datetime
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getPeriod(string $datetime): int
    {
        $period = 60;//базовая цена
        $date   = new DateTime($datetime);
        $time   = $date->format('H');
        if (!Tools::isWeekend($datetime)) {
            if ($time > 7 && $time < 17 && $time != 0) {
                $period = 60; // Будни до 17:00
            } elseif ($time >= 17 && $time < 23) {
                $period = 61; // Будни 17:00 - 23:00
            } elseif (($time >= 0 && $time <= 7) || $time == 23) {
                $period = 62; //Будни после 23:00
            }
        }
        if (Tools::isWeekend($datetime)) {
            if ($time > 7 && $time < 13 && $time != 0) {
                $period = 63; // Выходные до 13:00
            } elseif ($time >= 13 || ($time >= 0 && $time <= 7)) {
                $period = 64; // Выходные после 13:000
            }
        }
        return $period;
        
    }
    
    /**
     *
     * @return int
     */
    /**
     * Базовая цена за человека
     *
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getBasePriceByPerson(): int
    {
        return (int)$this->getBasePrice() / (int)$this->playersCnt;
    }
    
    /**
     * Цена за человека с учетом скидок
     *
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getOptimalPriceByPerson(): int
    {
        
        return (int)$this->getOptimalPrice() / (int)$this->playersCnt;
    }
    
    /**
     * Получить скидку карты лояльности привязанную к номеру
     *
     * @param string $phone
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDiscountByPhone(string $phone): int
    {
        $clearPhone = Tools::clearPhone($phone);
        $dbRes      = LoyaltyCardTable::query()
            ->setSelect(['DISCOUNT'])
            ->whereNotNull('ACTIVE')
            ->where('ACTIVE', 1)
            ->where('PHONE', $clearPhone)
            ->exec();
        return (int)$dbRes->fetch()['DISCOUNT'];
    }
    
    /**
     * Получить скидку карты лояльности
     * @param int $card
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDicountByCard(int $card): int
    {
        $exchangeCardFilter    = Query::filter()
            ->where('EXCHANGE', '!=', '')
            ->where('EXCHANGE', 'like', $card . '%');
        $notExchangeCardFilter = Query::filter()
            ->where('EXCHANGE', '')
            ->where('CODE', 'like', $card . '%');
        
        $dbRes  = LoyaltyCardTable::query()
            ->setSelect(['DISCOUNT'])
            ->whereNotNull('ACTIVE')
            ->where('ACTIVE', 1)
            ->where(Query::filter()
                ->logic('or')
                ->where($exchangeCardFilter)
                ->where($notExchangeCardFilter)
            )
            ->exec();
        $result = $dbRes->fetch();
        if (!$result && strlen($card) >= 2 && $this->throwExeption) {
            throw new ArgumentException('Не найдено активных карт с таким номером', 'FAIL');
        }
        
        return (int)$result['DISCOUNT'];
    }
    
    /**
     * Получить скидку по номеру сертификата
     *
     * @param string $certificate
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDiscountByCetrificate(string $certificate): array
    {
        $dateFilter = new DateTime($this->date);
        $dbRes      = CertificateTable::query()
            ->setSelect(['CERT_VALUE', 'FULL_PRICE'])
            ->where('CERT_NUM', trim($certificate))
            ->where('ACTIVE_TO', '>', $dateFilter)
            ->where('USED', 0)
            ->whereNotNull('CERT_NUM')
            ->whereNot('CERT_NUM', '')
            ->whereNot('CERT_NUM', 0)
            ->exec();
        $result = $dbRes->fetch();
        if (!$result && $this->throwExeption) {
            throw new ArgumentException('Не найдено активных сертифкатов с таким номером', 'FAIL');
        }
        if ($result['FULL_PRICE'] == 1) {
            $nominal = true;
        } else {
            $nominal = false;
        }
        return [
            'DISCOUNT' => $result['CERT_VALUE'],
            'NOMINAL'  => $nominal,
        ];
    }
    
    
    /**
     * @param string $date
     */
    public function setDate(string $date)
    {
        $this->date = $date;
    }
    
    /**
     * @param int $questId
     */
    public function setQuestId(int $questId)
    {
        $this->questId = $questId;
    }
    
    /**
     * @param int $playersCnt
     */
    public function setPlayersCnt(int $playersCnt)
    {
        $this->playersCnt = $playersCnt;
    }
    
    /**
     * @param string $phone
     */
    public function setPhone(string $phone)
    {
        $this->phone = $phone;
    }
    
    /**
     * @param string $loyaltyCard
     */
    public function setLoyaltyCard(string $loyaltyCard)
    {
        $this->loyaltyCard = $loyaltyCard;
    }
    
    /**
     * @param bool $photo
     */
    public function addPhotograf(bool $photo = true)
    {
        $this->photo = $photo;
    }
    
    /**
     * @param bool $animator
     */
    public function addAnimator(bool $animator = true)
    {
        $this->animator = $animator;
    }
    
    /**
     * @param bool $birthday
     */
    public function addBirthday(bool $birthday = true)
    {
        $this->birthday = $birthday;
    }
    
    /**
     * @param bool $lounge
     */
    public function addLounge(bool $lounge = true)
    {
        $this->lounge = $lounge;
    }
    
    /**
     * @param string $cetrificate
     */
    public function setCetrificate(string $cetrificate)
    {
        $this->certificateNum = $cetrificate;
    }
    
    /**
     * @param string $promocode
     */
    public function setPromocode(string $promocode)
    {
        $this->promocode = $promocode;
    }
    
    /**
     * @return string
     */
    public function getCertificateNum(): string
    {
        return $this->certificateNum;
    }
    
    /**
     * @return int
     */
    public function getCertificateValue(): int
    {
        return $this->certificateValue;
    }
    
    /**
     * @param bool $throwExeption
     */
    public function setThrowExeption(bool $throwExeption)
    {
        $this->throwExeption = $throwExeption;
    }
    
    
}
