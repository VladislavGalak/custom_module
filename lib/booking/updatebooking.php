<?php

namespace Webpractik\Main\Booking;


use Bitrix\Main\ArgumentException;
use Webpractik\Api\Response;
use Webpractik\Main\Sms;
use Webpractik\Main\Tools;

class UpdateBooking extends Response
{
    private $questId    = 0;
    private $date       = '';
    private $playersCnt = 2;
    private $bookingId  = 0;
    private $arQuests   = [];
    
    const PAID_STATUSES = [6, 7, 8];
    
    /**
     * Основной метод обработчика
     *
     * @throws \Exception
     */
    public function handler()
    {
        try {
            $this->arQuests = Tools::getArQuests();
            $this->setParams();
            $bookingManager = new BookingManager($this->questId, $this->date, $this->playersCnt);
            $bookingManager->setBookingId($this->bookingId);
            if ((int)$this->request->get('ADDITIONAL_1') == 1) {
                $bookingManager->setAddServices($bookingManager::BIRTHDAY);
            }
            if ((int)$this->request->get('ADDITIONAL_2') == 1) {
                $bookingManager->setAddServices($bookingManager::ANIMATOR);
            }
            if ((int)$this->request->get('ADDITIONAL_3') == 1) {
                $bookingManager->setAddServices($bookingManager::PHOTOGRAF);
            }
            if ((int)$this->request->get('ADDITIONAL_4') == 1) {
                $bookingManager->setAddServices($bookingManager::LOUNGE);
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
            $bookingManager->setPhone((string)$this->request->get('phone'));
            $bookingManager->setEmail((string)$this->request->get('email'));
            $bookingManager->setName((string)$this->request->get('clientName'));
            $bookingManager->setSource((int)$this->request->get('utm'));
            $bookingManager->setManager((int)$this->request->get('manager'));
            $bookingManager->setStatus((int)$this->request->get('status'));
            $bookingManager->setComment((string)$this->request->get('comment'));
            if (in_array($this->request->get('status'), self::PAID_STATUSES) && strlen((string)$this->request->get('certificate')) > 0) {
                Tools::makeCertificateUsed((string)$this->request->get('certificate'));
            }
            $arQuests    = $this->arQuests;
            $bookingInfo = Tools::getBookingInfo($this->bookingId);
            
            if ($bookingManager->update()) {
                if (in_array((int)$this->request->get('status'), [10, 11])
                    && !in_array($bookingInfo['STATUS_ID'], [10, 11])) { //если текущий статус брони не отменен и передан статус отменент, то посылаем письмо об отмене
                    $this->sendEmailCanceled(
                        [
                            'DATE'        => FormatDate('d F, l', MakeTimeStamp($this->request->get('date'))),
                            'EMAIL'       => (string)$this->request->get('email'),
                            'QUEST_NAME'  => $arQuests[$this->questId]['NAME'],
                            'CLIENT_NAME' => $this->request->get('clientName'),
                            'FIRST_IMG'   => SITE_SERVER_NAME . '/' . \CFile::ResizeImageGet($arQuests[$this->questId]['HEAD_IMG'], ['width' => 620, 'height' => 251])['src'],
                        ]
                    );
                }
                
                if ($bookingInfo['DATE_RESERVE']->format('U') != MakeTimeStamp($this->request->get('date')
                        . ' '
                        . $this->request->get('time'))) {//если переданная дата не совпадает с той которая уже установлена(т.е. изменили дату/время брони)
                    $datatime = $this->request->get('date') . ' ' . $this->request->get('time');
                    $this->sendEmailMoved(
                        [
                            'DATETIME'   => $datatime,
                            'BOOKING_ID' => $this->bookingId,
                        ]
                    );
                }
                
                
                if (in_array((int)$this->request->get('status'), [2]) && $bookingInfo['STATUS_ID'] != 2) {
                    // $this->sendSMS(+79889994203);
                    $this->sendEmailAccepted(
                        [
                            'DATE'        => FormatDate('d F, l', MakeTimeStamp($this->request->get('date'))),
                            'QUEST_NAME'  => $arQuests[$this->questId]['NAME'],
                            'EMAIL'       => (string)$this->request->get('email'),
                            'CLIENT_NAME' => $this->request->get('clientName'),
                            'FIRST_IMG'   => SITE_SERVER_NAME . '/' . \CFile::ResizeImageGet($arQuests[$this->questId]['HEAD_IMG'], ['width' => 620, 'height' => 251])['src'],
                        ]
                    );
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
        
        $this->bookingId = (int)$this->request->get('id');
        if ($this->bookingId < 1) {
            throw new ArgumentException('Неверный Id брони', 'FAIL_QUEST_ID');
        }
        
        $this->date = $this->request->get('date') . $this->request->get('time');
        if (strlen($this->date) < 1) {
            throw new ArgumentException('Не передана дата', 'FAIL_DATE');
        }
        
        $this->playersCnt = $this->request->get('players_cnt');
        if ($this->playersCnt < 2 || $this->playersCnt > 6) {
            throw new ArgumentException('Неверное кол-во игроков', 'FAIL_QUANTITY');
        }
    }
    
    private function sendEmailAccepted(array $info)
    {
        $arFileds = [
            'DATE'        => $info['DATE'],
            'QUEST_NAME'  => $info['QUEST_NAME'],
            'CLIENT_NAME' => $info['CLIENT_NAME'],
            'EMAIL_TO'    => $info['EMAIL'],
            'FIRST_IMG'   => $info['FIRST_IMG'],
        ];
        $siteId   = 's1';
        \CEvent::Send('BOOKING_ACCEPTED', $siteId, $arFileds);
    }
    
    private function sendEmailCanceled(array $info)
    {
        $arFileds = [
            'DATE'        => $info['DATE'],
            'QUEST_NAME'  => $info['QUEST_NAME'],
            'CLIENT_NAME' => $info['CLIENT_NAME'],
            'EMAIL_TO'    => $info['EMAIL'],
            'FIRST_IMG'   => $info['FIRST_IMG'],
        ];
        $siteId   = 's1';
        \CEvent::Send('BOOKING_CANCELED', $siteId, $arFileds);
    }
    
    private function sendEmailMoved(array $info)
    {
        $arFileds = [
            'DATETIME'   => $info['DATETIME'],
            'BOOKING_ID' => $info['BOOKING_ID'],
        ];
        $siteId   = 's1';
        \CEvent::Send('BOOKING_MOVED', $siteId, $arFileds);
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
