<?

namespace Webpractik\Main\Parser;

use Bitrix\Main\Type\DateTime;
use Webpractik\Main\Orm\LoyaltyCardTable;
use Webpractik\Main\Tools;

class CardParser
{
    /**
     * Добавить новую карту
     *
     * @param array $arData
     * @throws \Exception
     */
    public function addCard(array $arData)
    {
        LoyaltyCardTable::add($arData);
    }
    
    /**
     * Обновить существующую карту
     *
     * @param array $arData
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Exception
     */
    public function updateCard(array $arData)
    {
        $cardId = 0;
        if (strlen($arData['PHONE']) > 0) {
            $cardId = $this->getIdByPhone($arData['PHONE']);
            if ($cardId < 1) {
                $cardId = $this->getIdByCode($arData['CODE']);
            }
        }
        if ($cardId == 0 && strlen($arData['CODE'] > 0)) {
            $cardId = $this->getIdByCode($arData['CODE']);
        }
        // $data = [
        //     'CODE'     => $code,
        //     'DISCOUNT' => $discount,
        //     'PHONE'    => $phone,
        // ];
        LoyaltyCardTable::update($cardId, $arData);
    }
    
    /**
     * @param string $code
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getIdByCode(string $code)
    {
        $dbRes = LoyaltyCardTable::query()
            ->setSelect(['CARD_ID'])
            ->where('CODE', $code)
            ->exec();
        return (int)$dbRes->fetch()['CARD_ID'];
    }
    
    /**
     * @param string $phone
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getIdByPhone(string $phone)
    {
        $dbRes = LoyaltyCardTable::query()
            ->setSelect(['CARD_ID'])
            ->where('PHONE', $phone)
            ->exec();
        return (int)$dbRes->fetch()['CARD_ID'];
    }
    
    /**
     * @param string $phone
     * @param string $code
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function isExists(string $phone, string $code = ''): bool
    {
        $cardId = 0;
        if (strlen($phone) > 0) {
            $cardId = $this->getIdByPhone($phone);
            if ($cardId < 1) {
                $cardId = $this->getIdByCode($code);
            }
        }
        if ($cardId == 0 && strlen($code > 0)) {
            $cardId = $this->getIdByCode($code);
        }
        
        if ($cardId > 0) {
            return true;
        } else {
            return false;
        }
        
    }
    
    
    /**
     * Парсинг csvшки
     *
     * @param string $path
     * @return array
     * @throws \Bitrix\Main\ObjectException
     */
    public function parseTable(string $path): array
    {
        $arItems = array_map('str_getcsv', file($_SERVER['DOCUMENT_ROOT'] . $path));
        $result  = [];
        foreach ($arItems as $key => $item) {
            if ($key == 0) {
                continue;
            }
            $result[] =
                [
                    'CODE'          => $item[0],
                    'PHONE'         => Tools::clearPhone($item[2]),
                    'DISCOUNT'      => $item[1],
                    'OWNNER'        => $item[3],
                    'EMAIL'         => $item[4],
                    'EMISSION_DATE' => new DateTime($item[5]),
                    'ACTIVE'        => $item[6] == 'Да' ? 1 : 0,
                    'EXCHANGE'      => $item[7],
                    'LOCATION'      => $item[8],
                    'COMMENT'       => $item[9],
                ];
        }
        
        return $result;
    }
    
}
