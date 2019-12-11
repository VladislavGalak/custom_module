<?php

namespace Webpractik\Main;


use Bitrix\Main\ArgumentException;

class Sms
{
    private static $login        = 'musht';
    private static $password     = '91&0d(42';
    public static  $host         = 'api.smstraffic.ru';
    public static  $failoverHost = 'api2.smstraffic.ru';
    
    /**
     *Отправка SMS
     *
     * @param        $phone
     * @param        $message
     * @param string $originator
     * @param int    $rus
     * @param string $udh
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function send($phone, $message, $originator = 'KVESTOMANIA', $rus = 1, $udh = '')
    {
        $phone    = Tools::clearPhone($phone);
        $maxParts = 1;
        
        $path   = "/multi.php";
        $params = "login="
            . urlencode(self::$login)
            . "&password="
            . urlencode(self::$password)
            . "&want_sms_ids=1&phones=$phone&message="
            . urlencode($message)
            . "&max_parts=$maxParts&rus=$rus&originator="
            . urlencode($originator);
        if ($udh) {
            $params .= "&udh=" . urlencode($udh);
        }
        
        $response = self::httpPost(self::$host, $path, $params);
        if ($response == null) {
            $response = self::httpPost(self::$failoverHost, $path, $params);
            if ($response == null) {
                throw new ArgumentException('Ошибка отправки сообщения', 'FAIL_SEND');
            }
        }
        if (strpos($response, '<result>OK</result>')) {
            if (preg_match('|<sms_id>(\d+)</sms_id>|s', $response, $regs)) {
                $smsId = $regs[1];
                return [$smsId, 'OK'];
            } else {
                throw new ArgumentException('Ошибка отправки сообщения: не найдет sms_id', 'FAIL_SEND');
            }
        } elseif (preg_match('|<description>(.+?)</description>|s', $response, $regs)) {
            $error = $regs[1];
            throw new ArgumentException('Ошибка отправки сообщения: ' . $error, 'FAIL_SEND');
        } else {
            throw new ArgumentException('Ошибка отправки сообщения', 'FAIL_SEND');
        }
    }
    
    /**
     * Отправка запроса
     * @param $host
     * @param $path
     * @param $params
     * @return bool|null|string
     */
    public static function httpPost($host, $path, $params)
    {
        $httpResultCode = 0;
        $params_len     = strlen($params);
        $fp             = @fsockopen($host, 80);
        if (!$fp) {
            return null;
        }
        fputs(
            $fp,
            "POST $path HTTP/1.0\nHost: $host\nContent-Type: application/x-www-form-urlencoded\nUser-Agent: sms.php class 1.0 (fsockopen)\nContent-Length: $params_len\nConnection: Close\n\n$params\n"
        );
        $response = fread($fp, 8000);
        fclose($fp);
        if (preg_match('|^HTTP/1\.[01] (\d\d\d)|', $response, $regs)) {
            $httpResultCode = $regs[1];
        }
        return ($httpResultCode == 200) ? $response : null;
    }
    
    /**
     * Генерация рандомного кода для подтверждения номера телефона
     */
    public static function makeCode()
    {
        $_SESSION['SMS_CODE'] = randString(6, '0123456789');
        return $_SESSION['SMS_CODE'];
    }
    
    /**
     * Получение последнего запомненого кода
     * @return integer
     */
    public static function getLastCode()
    {
        return $_SESSION['SMS_CODE'];
    }
    
}
