<?php

use Com\Tecnick\Barcode\Barcode;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CC_FunctTwig extends AbstractExtension
{

    public function getFunctions()
    {
        return array(
            new TwigFunction('version', array($this, 'version')),
            new TwigFunction('num2str', array($this, 'Num2StrComponent')),
            new TwigFunction('formatBarcode', array($this, 'formatBarcode')),
        );
    }

    public function formatBarcode($args)
    {
        $args['t'] = $args['t'] ?? 'QRCODE,H';
        $args['s'] = $args['s'] ?? '';
        $args['w'] = $args['w'] ?? -3;
        $args['h'] = $args['h'] ?? -3;
        $args['c'] = $args['c'] ?? 'black';
        $args['bg'] = $args['bg'] ?? '#ffffff';

        $barcode = new Barcode();
        $bobj = $barcode->getBarcodeObj($args['t'], $args['s'], $args['w'], $args['h'], $args['c'], array(-2, -2, -2, -2))->setBackgroundColor($args['bg']);
        return '<img src="data:image/png;base64,' . base64_encode($bobj->getPngData()) . '">';
    }

    public function Num2StrComponent($p)
    {
        $out = '';
        $sum = $p['v'] ?? '0.00';

        if ($sum > 0) {
            $out = !isset($p['n2n']) ? $this->num2str($sum) : $sum;
        } else {
            if (isset($p['sa'])) {
                $out = !isset($p['n2n']) ? $this->num2str($sum) : $sum;
            }
        }
        return $out;
    }

    /**
     * Повертає суму прописом
     * @author runcore
     * @uses morph(...)
     */
    public function num2str($num)
    {
        $nul = 'нуль';
        $ten = array(
            array('', 'одна', 'дві', 'три', 'чотири', 'п`ять', 'шість', 'сім', 'вісім', 'дев`ять'),
            array('', 'одна', 'дві', 'три', 'чотири', 'п`ять', 'шість', 'сім', 'вісім', 'дев`ять'),
        );
        $a20 = array('десять', 'одиннадцять', 'дванадцять', 'тринадцять', 'чотирнадцять', 'п`ятнадцять', 'шістнадцять', 'сімнадцять', 'вісімнадцать', 'дев`ятнадцять');
        $tens = array(2 => 'двадцять', 'тридцять', 'сорок', 'п`ятдесят', 'шістдесят', 'сімьдесят', 'вісімдесят', 'дев`яносто');
        $hundred = array('', 'сто', 'двісті', 'триста', 'чотиреста', 'п`ятсот', 'шістьсот', 'сімьсот', 'вісімсот', 'дев`ятсот');
        $unit = array( // Units
            array('копійка', 'копійки', 'копійок', 1),
            array('гривня', 'гривні', 'гривень', 0),
            array('тисяча', 'тисячі', 'тисяч', 1),
            array('мілліон', 'мілліона', 'мілліонів', 0),
            array('мілліард', 'міліарда', 'мілліардів', 0),
        );
        //
        list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($num)));
        $out = array();
        if (intval($rub) > 0) {
            foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
                if (!intval($v)) continue;
                $uk = sizeof($unit) - $uk - 1; // unit key
                $gender = $unit[$uk][3];
                list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
                // mega-logic
                $out[] = $hundred[$i1]; # 1xx-9xx
                if ($i2 > 1) $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3]; # 20-99
                else $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
                // units without rub & kop
                if ($uk > 1) $out[] = $this->morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
            } //foreach
        } else $out[] = $nul;
        $out[] = $this->morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
        $out[] = $kop . ' ' . $this->morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
        return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
    }

    /**
     * Склоняем словоформу
     * @ author runcore
     */
    public function morph($n, $f1, $f2, $f5)
    {
        $n = abs(intval($n)) % 100;
        if ($n > 10 && $n < 20) return $f5;
        $n = $n % 10;
        if ($n > 1 && $n < 5) return $f2;
        if ($n == 1) return $f1;
        return $f5;
    }
}
