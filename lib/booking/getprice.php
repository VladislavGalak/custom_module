<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 16.08.18
 * Time: 18:33
 */

namespace Webpractik\Main\Booking;


use Bitrix\Main\ArgumentException;
use Webpractik\Api\Response;
use Webpractik\Main\Tools;

class GetPrice extends Response
{
    private $date       = '';
    private $playersCnt = 0;
    private $questId    = 0;
    
    
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
            
            /** @var array $arServices */
            
            $calculator = new BookingCalculator($this->date, $this->playersCnt, $this->questId);
            $arServices = $this->request->get('services');
            
            if ((int)$this->request->get('ADDITIONAL_1') == 1 || in_array('ADDITIONAL_1', $arServices)) {
                $calculator->addBirthday();
            }
            if ((int)$this->request->get('ADDITIONAL_2') == 1 || in_array('ADDITIONAL_2', $arServices)) {
                $calculator->addAnimator();
            }
            if ((int)$this->request->get('ADDITIONAL_3') == 1 || in_array('ADDITIONAL_3', $arServices)) {
                $calculator->addPhotograf();
            }
            if ((int)$this->request->get('ADDITIONAL_4') == 1 || in_array('ADDITIONAL_4', $arServices)) {
                $calculator->addLounge();
            }
            
            
            $calculator->setLoyaltyCard((string)$this->request->get('loyaltyCard'));
            $calculator->setPhone(Tools::clearPhone((string)$this->request->get('phone')));
            $calculator->setPromocode((string)$this->request->get('promocode'));
            $calculator->setCetrificate((string)$this->request->get('certificate'));
            $priceInfo = $calculator->calculate();
            $result    = [
                'players_cnt'        => $this->playersCnt,
                'promocode'          => $this->request->get('promocode'),
                'loyaltyCard'        => $this->request->get('loyaltyCard'),
                'phone'              => $this->request->get('phone'),
                'questPrice'         => $priceInfo['BASE_PRICE'],
                'optimalPrice'       => $priceInfo['OPTIMAL_PRICE'],
                'optimalPricePerOne' => $priceInfo['OPTIMAL_PRICE_BY_PRESON'],
                'discount'           => $priceInfo['DISCOUNT'],
                'discount_type'      => $this->convertDiscountType($priceInfo['DISCOUNT_TYPE']),
                'certificate'        => [
                    'id'    => $this->request->get('certificate'),
                    'value' => $priceInfo['CERTIFICATE_DISCOUNT'],
                ],
                'reason'             => $priceInfo['DISCOUNT_REASON'],
                'reasonId'           => $priceInfo['DISCOUNT_REASON_ID'],
                'totalPrice'         => $priceInfo['TOTAL_PRICE'],
            ];
            $this->response->addParam('priceInfo', $result)->sendSuccess();
            
        } catch (ArgumentException $e) {
            $this->response
                ->addParam('errorCode', $e->getParameter())
                ->addParam('errorText', $e->getMessage())
                ->send();
        }
        
        
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
    
    /**
     * @throws \Bitrix\Main\ArgumentException
     */
    private function setParams()
    {
        $this->questId = (int)$this->request->get('questId');
        if ($this->questId < 1) {
            throw new ArgumentException('Неверный Id', 'FAIL_QUEST_ID');
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
}
