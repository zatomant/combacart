<?php

use Com\Tecnick\Barcode\Barcode;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CC_FilterTwig extends AbstractExtension
{

    public function getFilters()
    {
        return array(
            new TwigFilter('translit', array($this, "formatTranslit")),
            new TwigFilter('clearEOF', array($this, "clearEOF")),

            new TwigFilter('formatphone', array($this, "formatPhone")),
            new TwigFilter('formatdeclaration', array($this, "formatDeclaration")),
            new TwigFilter('formatname', array($this, "formatName")),
            new TwigFilter('formatBarcode', array($this, "formatBarcode")),
            new TwigFilter('formatQRcode', array($this, "formatQRcode")),
            new TwigFilter('base64_encode', array($this, 'base64Encode')),
            new TwigFilter('base64_decode', array($this, 'base64Decode')),
            new TwigFilter('base64url_encode', array($this, 'base64UrlEncode')),
            new TwigFilter('base64url_decode', array($this, 'base64UrlDecode')),
        );
    }

    public function base64Encode(string $input): string
    {
        return base64_encode($input);
    }

    public function base64Decode(string $input): string
    {
        return base64_decode($input);
    }

    public function base64UrlEncode(string $input): string
    {
        $base64 = $this->base64Encode($input);
        return rtrim(strtr($base64, '+/', '-_'), '=');
    }

    public function base64UrlDecode(string $input): string
    {
        $base64 = strtr($input, '-_', '+/');
        return $this->base64Decode($base64);
    }

    public function formatQRcode($string)
    {
        $barcode = new Barcode();
        $bobj = $barcode->getBarcodeObj('QRCODE,H', $string, -3, -3, 'black', array(-2, -2, -2, -2))->setBackgroundColor('#ffffff');
        return '<img src="data:image/png;base64,' . $this->base64Encode($bobj->getPngData()) . '">';
    }

    public function formatBarcode($string)
    {
        $barcode = new Barcode();
        $bobj = $barcode->getBarcodeObj('C128', $string, -2, -30, 'black', array(0, 0, 0, 0));
        return '<img src="data:image/png;base64,' . $this->base64Encode($bobj->getPngData()) . '">';
    }

    public function clearEOF($string)
    {
        return preg_replace('/
    ^
    [\pZ\p{Cc}\x{feff}]+
    |
    [\pZ\p{Cc}\x{feff}]+$
   /ux',
            '', $string);
    }

    public function formatTranslit($string)
    {

        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e', 'є' => 'ye',
            'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'і' => 'i',
            'и' => 'i', 'й' => 'j', 'к' => 'k', 'ї' => 'yi',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'kh', 'ц' => 'ts',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
            'ь' => '\'', 'ы' => 'y', 'ъ' => '"',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Є' => 'Ye',
            'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'І' => 'I',
            'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Ї' => 'Yi',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch',
            'Ь' => '\'', 'Ы' => 'Y', 'Ъ' => '"',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        );
        $result = strtr($string, $converter);

        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $result);
    }

    public function formatName($str, $delim = ' ', $limit = 2)
    {
        $out = '';
        $str = rtrim(ltrim($str));
        $key = explode($delim, $str);
        $key = array_reverse($key);
        for ($n = 0; $n < count($key); $n++) {
            if ($n < $limit) {
                if (count($key) <= 2 && $n == 1) return $out;
                $out = $key[$n] . " " . $out;
            }
        }

        return $out;
    }

    public function formatDeclaration($str, $raw = 0)
    {
        $out = '';
        if ($raw != 0) return $str;

        if (!empty($str)) {

            $pat = '/([0-9])/';
            $rpl = '$1';

            if (!empty($str) && strlen($str) == 14) { // novaposhta
                $pat = '/([0-9]{2})([0-9]{4})([0-9]{4})([0-9]{4})/';
                $rpl = '$1 $2 $3 $4';
            }

            if (!empty($str) && strlen($str) == 10) { // intime
                $pat = '/([0-9]{4})([0-9]{2})([0-9]{4})/';
                $rpl = '$1 $2 $3';
            }

            if (!empty($str) && strlen($str) == 13) { //ukrposhta
                $pat = '/([0-9]{5})([0-9]{4})([0-9]{4})/';
                $rpl = '$1 $2 $3';
            }

            $out .= preg_replace($pat, $rpl, $str);
        }
        return $out;
    }

    public function formatPhone($number)
    {
        // Видаляємо всі непотрібні символи
        $cleaned = preg_replace('/[^\d+]/', '', $number);

        // Визначаємо країну та місцевий код
        if (strpos($cleaned, '+') === 0) {
            $countryCode = substr($cleaned, 0, strlen($cleaned) - 10);
            $localNumber = substr($cleaned, -10);
        } elseif (strlen($cleaned) > 10) {
            $countryCode = substr($cleaned, 0, strlen($cleaned) - 10);
            $localNumber = substr($cleaned, -10);
        } else {
            $countryCode = '';
            $localNumber = $cleaned;
        }

        // Форматування локального номера
        $formattedLocalNumber = sprintf("(%s) %s-%s-%s",
            substr($localNumber, 0, 3),
            substr($localNumber, 3, 3),
            substr($localNumber, 6, 2),
            substr($localNumber, 8, 2)
        );

        // Об'єднання з кодом країни
        return $countryCode ? $countryCode . ' ' . $formattedLocalNumber : $formattedLocalNumber;
    }

}
