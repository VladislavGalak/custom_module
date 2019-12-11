<?php

namespace Webpractik\Main\Response;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\DateTime;
use Webpractik\Api\Response;
use Webpractik\Main\Orm\CertificateTable;
use Webpractik\Main\Tools;

class CreateCertificate extends Response
{
    private $clientName      = '';
    private $phone           = '';
    private $email           = '';
    private $certificateType = 0;
    private $deliveryType    = 0;
    private $arCertNum       = [];
    
    public function handler()
    {
        try {
            $certTypes     = Tools::getCertificateTypes();
            $this->setParams();
                $data    = [
                    /*'CERT_NUM'   => $certNum,*/
                    'CERT_VALUE' => $certTypes[$this->certificateType]['VALUE'],
                    'FULL_PRICE' => $certTypes[$this->certificateType]['FULL_PRICE'],
                    'USED'       => 0,
                    'BUYER'      => $this->clientName,
                    'BYER_PHONE' => $this->phone,
                    'DEAL_DATE'  => new DateTime(),
                    'DELIVERY'   => $this->deliveryType,
                ];
                CertificateTable::add($data);
                LocalRedirect('/bitrix/admin/highloadblock_rows_list.php?ENTITY_ID=3&lang=ru');
    
        } catch (ArgumentException $e) {
            $this->response
                ->addParam('errorCode', $e->getParameter())
                ->addParam('errorText', $e->getMessage())
                ->send();
        }
    }
    
    /**
     * Установка и проверка параметров
     *
     * @throws \Bitrix\Main\ArgumentException
     */
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
        $this->certificateType = (int)$this->request->get('certificateType');
        //if ($this->certificateType < 1) {
          //  throw new ArgumentException('Не выбран тип сертификата', 'FAIL_DATA');
        //}
        $this->deliveryType = (int)$this->request->get('deliveryType');
        //if ($this->deliveryType < 1) {
          //  throw new ArgumentException('Не выбран способ доставки', 'FAIL_DATA');
        //}
    }
    
    /**
     * Проверяет существует ли сертификат с таким номером
     *
     * @param int $certNum
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function isCertExist(int $certNum):bool
    {
        $certId = 0;
        $dbRes = CertificateTable::query()
            ->setSelect(['CERT_ID'])
            ->where('CERT_NUM', $certNum)
            ->exec();
        while ($cert = $dbRes->fetch()){
            $certId = $cert['CERT_ID'];
        }
        if ($certId > 0) {
            return true;
        }else{
            return false;
        }
        
    }
}


