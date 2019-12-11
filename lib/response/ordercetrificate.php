<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 13.09.18
 * Time: 15:25
 */

namespace Webpractik\Main\Response;


use Bitrix\Main\ArgumentException;
use Webpractik\Api\Response;
use Webpractik\Main\Tools;
use Bitrix\Main\Mail\Event;


class OrderCetrificate extends Response
{
    private $clientName      = '';
    private $phone           = '';
    private $email           = '';
    private $count           = 0;
    private $certificateType = 0;
    private $deliveryType    = 0;
    
    public function handler()
    {
        try {
            $this->setParams();
            $certTypes = Tools::getCertificateTypes();
            $confirmLnk = '/api/form/createcert/';
            $confirmLnk .= '?name=' . $this->clientName;
            $confirmLnk .= '&phone=' . $this->phone;
            $confirmLnk .= '&email=' . $this->email;
            $confirmLnk .= '&certificateType=' . $this->certificateType;
            $confirmLnk .= '&deliveryType=' . $this->deliveryType;
            $arFields   = [
                'CLIENT_NAME'  => $this->clientName,
                'PHONE'        => $this->phone,
                'EMAIL'        => $this->email,
                'DELIVERY'     => $this->deliveryType == 0 ? 'Самовывоз' : 'Доставка',
                'TYPE'         => $certTypes[$this->certificateType]['NAME'],
                'CONFIRM_LINK' => $confirmLnk,
            ];
            if (Event::send([
                "EVENT_NAME" => "NEW_CERT_ORDER",
                "LID"        => "s1",
                "C_FIELDS"   => $arFields,
            ])->isSuccess()) {
                $this->saveToDb();
                $this->response->sendSuccess();
            };
            
            
        } catch (ArgumentException $e) {
            $this->response
                ->addParam('errorCode', $e->getParameter())
                ->addParam('errorText', $e->getMessage())
                ->send();
        }
    }
    
    private function setParams()
    {
        $this->clientName = (string)$this->request->get('name');
        if (strlen($this->clientName) < 1) {
            throw new ArgumentException('Не введено имя', 'FAIL_DATA');
        }
        $this->phone = (string)$this->request->get('phone');
        if (strlen($this->phone) < 1) {
            throw new ArgumentException('Не введен телефон', 'FAIL_DATA');
        }
        $this->email = (string)$this->request->get('email');
        if (strlen($this->email) < 1) {
            throw new ArgumentException('Не введен email', 'FAIL_DATA');
        }
        $this->count = (int)$this->request->get('count');
        if ($this->count < 1) {
            throw new ArgumentException('Не введено количество', 'FAIL_DATA');
        }
        $this->certificateType = (int)$this->request->get('certificateType');
        /*if (is_int($this->certificateType)) {
            throw new ArgumentException('Не выбран тип сертификата', 'FAIL_DATA');
        }*/
        $this->deliveryType = (int)$this->request->get('deliveryType');
        /*if (is_int($this->deliveryType < 1)) {
            throw new ArgumentException('Не выбран способ доставки', 'FAIL_DATA');
        }*/
        if (strlen($this->request->get('birthday'))>0) {
            throw new ArgumentException('Спам', 'FAIL');
        }
    }
    
    /**
     * Сохранить заявку в инфоблок
     */
    private function saveToDb()
    {
        
        $PROP['PHONE'] = Tools::clearPhone($this->phone);
        $PROP['EMAIL'] = $this->email;
        $PROP['COUNT'] = $this->count;
        $PROP['TYPE'] = $this->certificateType;
        $arData = [
            "MODIFIED_BY"       => 1,
            "IBLOCK_SECTION_ID" => false,
            "PROPERTY_VALUES"   => $PROP,
            "NAME"              => $this->clientName,
            "ACTIVE"            => "Y",
        ];
        Tools::saveCertOrderToDb($arData);
    }
}
