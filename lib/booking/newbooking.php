<?php

namespace Webpractik\Main\Booking;


use Bitrix\Main\ArgumentException;
use Webpractik\Api\Response;
use Webpractik\Main\Sms;
use Webpractik\Main\Tools;

// use Webpractik\Main\Sms;

class NewBooking extends Response
{
    private $questId    = 0;
    private $date       = '';
    private $playersCnt = 2;
    private $clientName = '';
    // private $smsCode    = '';
    
    const SPAN_STYLE = '-webkit-text-size-adjust:none; font-family:Tahoma,sans-serif; display:block; background-color:transparent; color:#f82064; line-height:13px; letter-spacing:normal; font-weight:bold; font-size:12px;';
    
    /**
     * Основной метод обработчика
     *
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\SystemException
     */
    public function handler()
    {
        try {
            
            $this->setParams();
            
            $calculator = new BookingCalculator($this->date, $this->playersCnt, $this->questId);
            $calculator->setPhone(Tools::clearPhone((string)$this->request->get('phone')));
            $calculator->setPromocode((string)$this->request->get('promocode'));
            $calculator->setLoyaltyCard((string)$this->request->get('loyaltyCard'));
            $calculator->setCetrificate((string)$this->request->get('certificate'));
            
            $strServices = '';
            
            $bookingManager = new BookingManager($this->questId, $this->date, $this->playersCnt);
            if ((int)$this->request->get('ADDITIONAL_1') == 1) {
                $bookingManager->setAddServices($bookingManager::BIRTHDAY);
                $calculator->addBirthday();
                $strServices .= '<tr><td align="left" valign="top" height="20"><span style="'. self::SPAN_STYLE.'"><b>День рождения</b></span></td></tr>';
            }
            if ((int)$this->request->get('ADDITIONAL_2') == 1) {
                $bookingManager->setAddServices($bookingManager::ANIMATOR);
                $calculator->addAnimator();
                $strServices .= '<tr><td align="left" valign="top" height="20"><span style="'. self::SPAN_STYLE.'"><b>Аниматор</b></span></td></tr>';
            }
            if ((int)$this->request->get('ADDITIONAL_3') == 1) {
                $bookingManager->setAddServices($bookingManager::PHOTOGRAF);
                $calculator->addPhotograf();
                $strServices .= '<tr><td align="left" valign="top" height="20"><span style="'. self::SPAN_STYLE.'"><b>Фотграф в квесте</b></span></td></tr>';
            }
            if ((int)$this->request->get('ADDITIONAL_4') == 1) {
                $bookingManager->setAddServices($bookingManager::LOUNGE);
                $calculator->addLounge();
                $strServices .= '<tr><td align="left" valign="top" height="20"><span style="'. self::SPAN_STYLE.'"><b>Гостевая зона</b></span></td></tr>';
            }
            
            $fullStrServices = '';
            if (strlen($strServices) > 0) {
                $fullStrServices = ' <tr><td align="left" valign="top" height="20"><spanstyle="-webkit-text-size-adjust:none; font-family:Tahoma,sans-serif; padding-top:3px; display:block; background-color:transparent; color:#1a1a1a; line-height:12px; letter-spacing:normal; font-weight:normal; font-size:12px;">Дополнительные услуги:</span></td></tr>';
                $fullStrServices .= $strServices;
            }
            
            
            if (strlen($this->request->get('saleReasonCustom')) > 0) {
                $bookingManager->setCustomReason((string)$this->request->get('saleReasonCustom'));
            } else {
                $bookingManager->setDiscountReason((string)$this->request->get('saleReason'));
                $bookingManager->setCustomReason('');
            }
            $bookingManager->setCertificate((string)$this->request->get('certificate'));
            $bookingManager->setLoyaltyCard((string)$this->request->get('loyaltyCard'));
            $bookingManager->setPromocode((string)$this->request->get('promocode'));
            $bookingManager->setPhone(Tools::clearPhone((string)$this->request->get('phone')));
            $bookingManager->setEmail((string)$this->request->get('email'));
            $bookingManager->setName($this->clientName);
            $bookingManager->setSource(55);//источник звонок
            $bookingManager->setManager(Tools::userId());
            $bookingManager->setStatus(1);//новый
            $strStatus = 'Новый';
            if ($this->request->get('admin') == true) {//если бронь создается из интерфеса менеджера
                $bookingManager->activate();
                if ((int)$this->request->get('status') == 0) {
                    $bookingManager->setStatus(2);//подтвержден
                    
                } else {
                    $strStatus = $this->request->get('statusName');
                    $bookingManager->setStatus((int)$this->request->get('status'));//подтвержден
                }
            }
            $bookingManager->setComment((string)$this->request->get('comment'));
          
            if ($bookingManager->addNew()) {
                $arQuests = Tools::getArQuests();
                $this->sendEmail([
                    'DATE'         => FormatDate('d F, l', MakeTimeStamp($this->request->get('date'))),
                    'TIME'         => (string)$this->request->get('time'),
                    'QUEST_NAME'   => $arQuests[$this->questId]['NAME'],
                    'LINK'         => SITE_SERVER_NAME . $arQuests[$this->questId]['LINK'],
                    'CLIENT_NAME'  => $this->clientName,
                    'EMAIL'        => (string)$this->request->get('email'),
                    'PHONE'        => (string)$this->request->get('phone'),
                    'CNT'          => $this->playersCnt,
                    'STATUS'       => $strStatus,
                    'TOTAL_PRICE'  => $calculator->getTotalPrice(),
                    'TICKET_PRICE' => $calculator->getOptimalPriceByPerson(),
                    'ADD_SERVICES' => $fullStrServices,
                    'LOCATION'     => $arQuests[$this->questId]['LOCATION'],
                    
                    'FIRST_IMG'  => SITE_SERVER_NAME . '/' . \CFile::ResizeImageGet($arQuests[$this->questId]['HEAD_IMG'], ['width' => 620, 'height' => 251],
                            BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'],
                    'SECOND_IMG' => SITE_SERVER_NAME . '/' . \CFile::ResizeImageGet($arQuests[$this->questId]['BODY_IMG'], ['width' => 271, 'height' => 250],
                            BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'],
                
                ]);
                if($bookingManager->getStatus() == 2) {
                    ;
                    // $this->sendSMS((string)$this->request->get('phone'));
                }
                $this->response->sendSuccess();
            }
            
        } catch (ArgumentException $e) {
            $this->response
                ->addParam('errorCode', $e->getParameter())
                ->addParam('errorText', $e->getMessage())
                ->send();
        }
    }
    
    /**
     * Валидация и установка входящих параметров
     *
     * @throws \Bitrix\Main\ArgumentException
     */
    private function setParams()
    {
        $this->questId = (int)$this->request->get('questId');
        if ($this->questId < 1) {
            throw new ArgumentException('Неверный Id квеста', 'FAIL_QUEST_ID');
        }
        
        $this->date = $this->request->get('date') . ' ' . $this->request->get('time');
        if (strlen($this->date) < 1) {
            throw new ArgumentException('Не передана дата', 'FAIL_DATE');
        }
        
        $this->playersCnt = (int)$this->request->get('players_cnt');
        if ($this->playersCnt < 2 || $this->playersCnt > 6) {
            throw new ArgumentException('Неверное кол-во игроков', 'FAIL_QUANTITY');
        }
        
        $this->clientName = (string)$this->request->get('clientName');
        if (strlen($this->clientName)<1) {
            $this->clientName = (string)$this->request->get('name');
        }
        
        if (strlen($this->clientName)<1) {
            throw new ArgumentException('Заполните имя клиента', 'FAIL');
        }
        if (strlen(Tools::clearPhone((string)$this->request->get('phone')))<1) {
            throw new ArgumentException('Заполните номер телефона', 'FAIL');
        }
        
        // $this->smsCode = $this->request->get('smsCode');
        // if (strlen($this->smsCode) < 1 || $this->smsCode != Sms::getLastCode()) {
        //     throw new ArgumentException('Неверный код из смс', 'WRONG_SMS_CODE');
        // }
    }
    
    private function sendEmail(array $info)
    {
        $arFileds = [
            'DATE'         => $info['DATE'],
            'TIME'         => $info['TIME'],
            'QUEST_NAME'   => $info['QUEST_NAME'],
            'CLIENT_NAME'  => $info['CLIENT_NAME'],
            'EMAIL_TO'     => $info['EMAIL'],
            'PHONE'        => $info['PHONE'],
            'CNT'          => $info['CNT'],
            'TOTAL_PRICE'  => $info['TOTAL_PRICE'],
            'TICKET_PRICE' => $info['TICKET_PRICE'],
            'STATUS'       => $info['STATUS'],
            'LINK'         => $info['LINK'],
            'ADDRESS'      => $info['LOCATION'],
            'ADD_SERVICES' => $info['ADD_SERVICES'],
            'FIRST_IMG'    => $info['FIRST_IMG'],
            'SECOND_IMG'   => $info['SECOND_IMG'],
        ];
        $siteId   = 's1';
        \CEvent::Send('NEW_BOOKING', $siteId, $arFileds);
    }
    
    private function sendSMS(string $phone)
    {
        if (strlen(Tools::clearPhone($phone) < 0)) {
            return;
        }
        $message = "Бронирование квеста подтверждено";
        
        Sms::send(Tools::clearPhone($phone), iconv('utf-8', 'windows-1251', $message));
    }
}
