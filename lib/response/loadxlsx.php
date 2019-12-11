<?php

namespace Webpractik\Main\Response;


use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use Webpractik\Api\Response;

class LoadXlsx extends Response
{
    private $rowNum     = 5;
    private $sampleData = [];
    
    public function handler()
    {
        $this->setParams();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Webpractik Vladyan')
            ->setLastModifiedBy('Webpractik Vladyan')
            ->setTitle('Report')
            ->setSubject('Report')
            ->setDescription('Отчет о бронировании квестов.');
        
        //заголовок таблицы
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Квест')
            ->setCellValue('B1', $this->sampleData['questName'])
            ->setCellValue('A4', 'ID')
            ->setCellValue('B4', 'Дата')
            ->setCellValue('C4', 'Время')
            ->setCellValue('D4', 'Кол-во игроков')
            ->setCellValue('E4', 'Цена квеста')
            ->setCellValue('F4', 'Др')
            ->setCellValue('G4', 'Аниматор')
            ->setCellValue('H4', 'Фотограф')
            ->setCellValue('I4', 'Гостевая зона')
            ->setCellValue('J4', 'Основание для скидки')
            ->setCellValue('K4', 'Промокод')
            ->setCellValue('L4', 'Скидка')
            ->setCellValue('M4', 'Номер сертификата')
            ->setCellValue('N4', 'Номинал сертификата')
            ->setCellValue('O4', 'Карта лояльности')
            ->setCellValue('P4', 'Итоговая цена')
            ->setCellValue('Q4', 'Телефон')
            ->setCellValue('R4', 'Email')
            ->setCellValue('S4', 'Клиент')
            ->setCellValue('T4', 'Источник')
            ->setCellValue('U4', 'Менеджер')
            ->setCellValue('V4', 'Статус')
            ->setCellValue('W4', 'Комментарий');
        //применяем стиль заголовка
        $styles = new Style();
        $styles->applyFromArray(
            [
                'fill'    => [
                    'fillType' => Fill::FILL_SOLID,
                    'color'    => ['argb' => 'FFd8d8d8'],
                ],
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
                    'right'  => ['borderStyle' => Border::BORDER_MEDIUM],
                    'top'    => ['borderStyle' => Border::BORDER_MEDIUM],
                    'left'   => ['borderStyle' => Border::BORDER_MEDIUM],
                ],
            ]);
        $arCells = range('A', 'W');
        foreach ($arCells as $cell) {
            $spreadsheet->getActiveSheet()
                ->getColumnDimension($cell)
                ->setAutoSize(true);
        }
        
        $spreadsheet->getActiveSheet()
            ->duplicateStyle($styles, 'A4:W4');
        
        //заполняем таблицу переданными данными
        foreach ($this->sampleData['bookingInfo'] as $booking) {
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $this->rowNum, $booking['id'])
                ->setCellValue('B' . $this->rowNum, $booking['date'])
                ->setCellValue('C' . $this->rowNum, $booking['time'])
                ->setCellValue('D' . $this->rowNum, $booking['playerCnt'])
                ->setCellValue('E' . $this->rowNum, $booking['questPrice'])
                ->setCellValue('F' . $this->rowNum, $booking['additional_1'])
                ->setCellValue('G' . $this->rowNum, $booking['additional_2'])
                ->setCellValue('H' . $this->rowNum, $booking['additional_3'])
                ->setCellValue('I' . $this->rowNum, $booking['additional_4'])
                ->setCellValue('J' . $this->rowNum, $booking['saleReason'])
                ->setCellValue('K' . $this->rowNum, $booking['promocode'])
                ->setCellValue('L' . $this->rowNum, $booking['discount'])
                ->setCellValue('M' . $this->rowNum, $booking['certNum'])
                ->setCellValue('N' . $this->rowNum, $booking['certVal'])
                ->setCellValue('O' . $this->rowNum, $booking['cardNum'])
                ->setCellValue('P' . $this->rowNum, $booking['totalPrice'])
                ->setCellValue('Q' . $this->rowNum, $booking['phone'])
                ->setCellValue('R' . $this->rowNum, $booking['email'])
                ->setCellValue('S' . $this->rowNum, $booking['clientName'])
                ->setCellValue('T' . $this->rowNum, $booking['utm'])
                ->setCellValue('U' . $this->rowNum, $booking['manager'])
                ->setCellValue('V' . $this->rowNum, $booking['status'])
                ->setCellValue('W' . $this->rowNum, $booking['comment']);
            $this->rowNum++;
        }
        
        //выводим статистику в конец таблицы
        $this->rowNum += 3;
        $spreadsheet->setActiveSheetIndex(0)
            ->duplicateStyle($styles, 'B' . $this->rowNum)
            ->setCellValue('B' . $this->rowNum, 'Статистика')
            ->setCellValue('B' . ++$this->rowNum, 'Количество игроков:')
            ->setCellValue('C' . $this->rowNum, $this->sampleData['statistic']['totalPlayersCnt'])
            ->setCellValue('B' . ++$this->rowNum, 'Количество игр:')
            ->setCellValue('C' . $this->rowNum, $this->sampleData['statistic']['gamesCnt'])
            ->setCellValue('B' . ++$this->rowNum, 'Среднее количество игроков:')
            ->setCellValue('C' . $this->rowNum, $this->sampleData['statistic']['playersCnt'])
            ->setCellValue('B' . ++$this->rowNum, 'Средняя скидка:')
            ->setCellValue('C' . $this->rowNum, $this->sampleData['statistic']['discountPercent'])
            ->setCellValue('B' . ++$this->rowNum, 'Средняя скидка (руб.):')
            ->setCellValue('C' . $this->rowNum, $this->sampleData['statistic']['discountRub'])
            ->setCellValue('B' . ++$this->rowNum, 'Сумма сертификатами:')
            ->setCellValue('C' . $this->rowNum, $this->sampleData['statistic']['certSumm'])
            ->setCellValue('B' . ++$this->rowNum, 'Общая сумма:')
            ->setCellValue('C' . $this->rowNum, $this->sampleData['statistic']['totalSumm']);
    
    
        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="01simple.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
    
        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        die();
    }
    
    /**
     * Получение и разбор параметров
     */
    public function setParams()
    {
        $data = json_decode($this->request->get('data'));
        /** @var object $statistic */
        $statistic        = $data->RESULTS;
        $arLangMap        = [0 => 'Нет', 1 => 'Да'];
        $this->sampleData =
            [
                'questName'   => $data->QUEST_NAME,
                'statistic'   => [
                    'totalPlayersCnt' => $statistic->totalPlayer,
                    'gamesCnt'        => $statistic->gameCount,
                    'playersCnt'      => $statistic->delta,
                    'discountPercent' => $statistic->deltaDiscount . '%',
                    'discountRub'     => $statistic->deltaDiscountRub . ' ₽',
                    'certSumm'        => $statistic->summSerts . ' ₽',
                    'totalSumm'       => $statistic->totalPrice . ' ₽',
                ],
                'bookingInfo' =>
                    [],
            ];
        foreach ($data->LIST as $booking) {
            $this->sampleData['bookingInfo'][] =
                [
                    'id'           => $booking->id,
                    'date'         => $booking->date,
                    'time'         => $booking->time,
                    'playerCnt'    => $booking->players_cnt,
                    'questPrice'   => $booking->questPrice,
                    'additional_1' => $arLangMap[$booking->ADDITIONAL_1],
                    'additional_2' => $arLangMap[$booking->ADDITIONAL_2],
                    'additional_3' => $arLangMap[$booking->ADDITIONAL_3],
                    'additional_4' => $arLangMap[$booking->ADDITIONAL_4],
                    'saleReason'   => $booking->saleReasonName,
                    'promocode'    => $booking->promocode,
                    'discount'     => $booking->discount,
                    'certNum'      => $booking->certificate,
                    'certVal'      => $booking->certificateNominal,
                    'cardNum'      => $booking->loyaltyCard,
                    'totalPrice'   => $booking->totalPrice,
                    'phone'        => $booking->phone,
                    'email'        => $booking->email,
                    'clientName'   => $booking->clientName,
                    'utm'          => $booking->utmName,
                    'manager'      => $booking->managerName,
                    'status'       => $booking->statusName,
                    'comment'      => $booking->comment,
                ];
        }
    }
    
}
