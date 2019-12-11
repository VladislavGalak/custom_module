<?php

namespace Webpractik\Main\Parser;


use Webpractik\Main\Orm\CertificateTable;

class CertParser
{
    private $arExistsCert = [];
    private $arPayTypes   =
        [
            'Наличные'  => 78,
            'Безнал'    => 79,
            'Перевод'   => 80,
            'Бартер'    => 81,
            'Бесплатно' => 82,
        ];
    
    public function __construct()
    {
        $this->arExistsCert = $this->getExists();
    }
    
    public function parseTable(string $path)
    {
        $arItems = array_map('str_getcsv', file($_SERVER['DOCUMENT_ROOT'] . $path));
        $result  = [];
        foreach ($arItems as $key => $item) {
            if ($key == 0) {
                continue;
            }
            $result[] =
                [
                    'CERT_NUM'         => $item[0],
                    'CERT_VALUE'       => $item[1],
                    'FULL_PRICE'       => $item[2] == 'Да' ? 1 : 0,
                    'USED'             => $item[3] == 'Да' ? 1 : 0,
                    'ACTIVE_TO'        => new \Bitrix\Main\Type\Date($item[4]),
                    'BUYER'            => $item[5],
                    'BYER_PHONE'       => str_replace([' ', '(', ')', '-', '+'], '', $item[6]),
                    'HOLDER_PHONE'     => str_replace([' ', '(', ')', '-', '+'], '', $item[7]),
                    'DEAL_DATE'        => new \Bitrix\Main\Type\Date($item[8]),
                    'DELIVERY'         => $item[9] == 'Да' ? 1 : 0,
                    'DELIVERY_ADDRESS' => $item[10],
                    'COMMENT'          => $item[12],
                    'PAID'             => $this->arPayTypes[$item[11]],
                ];
        }
        
        return $result;
    }
    
    /**
     * Существует ли номер сертификата
     *
     * @param string $certNum
     * @return bool
     */
    public function isExist(string $certNum): bool
    {
        return in_array($certNum, array_keys($this->arExistsCert));
    }
    
    /**
     * Создать сертификат
     *
     * @param array $arData
     * @throws \Exception
     */
    public function addCert(array $arData)
    {
        $addRes = CertificateTable::add($arData);
        $this->addToExist($addRes->getId(), $arData['CERT_NUM']);
    }
    
    /**
     * обновить существующий сертификат
     *
     * @param array $arData
     * @throws \Exception
     */
    public function updateCert(array $arData)
    {
        CertificateTable::update($this->getId($arData['CERT_NUM']), $arData);
    }
    
    /**
     * Добавить в коллекицю существующих сертификатов новый
     *
     * @param int    $id
     * @param string $certNum
     */
    private function addToExist(int $id, string $certNum)
    {
        $this->arExistsCert[$certNum] = $id;
    }
    
    /**
     * получить Id существующего сертификата
     *
     * @param string $certNum
     * @return int
     */
    private function getId(string $certNum): int
    {
        return $this->arExistsCert[$certNum];
    }
    
    /**
     * Поулчить список существующих сертификатов
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getExists(): array
    {
        $result = [];
        $dbRes  = CertificateTable::query()
            ->setSelect(['CERT_ID', 'CERT_NUM'])
            ->exec();
        while ($cert = $dbRes->fetch()) {
            $result[$cert['CERT_NUM']] = $cert['CERT_ID'];
        }
        return $result;
    }
    
}
