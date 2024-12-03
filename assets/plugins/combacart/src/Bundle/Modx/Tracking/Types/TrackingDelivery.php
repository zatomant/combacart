<?php

namespace Comba\Bundle\Modx\Tracking\Types;

/**
 * Class tracking for Delivery
 */
class TrackingDelivery extends TrackingNone
{
    protected string $title = 'Делівері';
    protected string $url = 'https://www.delivery-auto.com/';
    protected string $urltracking = 'https://www.delivery-auto.com/ru-RU/Receipts/GetTrackingForSearch?';

    /**
     * Remove trash
     *
     * @param string $out html
     *
     * @return string
     */
    public function clearScriptStyle(string $out): string
    {
        $out = preg_replace("'<script[^>]*?>.*?</script>'si", "", $out);
        return preg_replace("'<style[^>]*?>.*?</style>'si", "", $out);
    }

    public function getBarcodeInfo(string $declaration): ?string
    {
        return parent::getBarcodeInfo($declaration);
        /*
                $url = 'http://www.delivery-auto.com/ru-RU/Receipts/GetTrackingForSearch?';
                $data = array('number' => $declaration);

                $options = array(
                'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                ),
            );

                $context  = stream_context_create($options);
                $result = file_get_contents($url, false, $context);

                $ret = $result;
                $ret = $this->clearScriptStyle($ret);
                return $ret;
                */
    }


    public function getSupportType(): array
    {
        return array('dt_delivery');
    }
}
