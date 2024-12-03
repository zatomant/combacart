<?php

namespace Comba\Bundle\Modx\Tracking\Types;

use Exception;
use SimpleXMLElement;
use function get3thAuth;

/**
 * Tracking class of NovaPoshta
 */
class TrackingNovaposhta extends TrackingNone
{
    protected string $title = 'Нова Пошта';
    protected string $url = 'https://novaposhta.ua/';
    protected string $urltracking = 'https://novaposhta.ua/tracking/?cargo_number=';

    public function getBarcodeInfo(string $declaration): ?string
    {

        if (!parent::getBarcodeInfo($declaration)) {
            return null;
        }

        $auth = get3thAuth('NovaPoshta');
        $xml = false;
        if (!empty($auth)) {
            $xml =
                '<?xml version="1.0" encoding="UTF-8"?>
        <root>
        <apiKey>' . $auth["key"] . '</apiKey>
        <modelName>TrackingDocument</modelName>
        <calledMethod>getStatusDocuments</calledMethod>
        <methodProperties>
        <Documents><item>
        <DocumentNumber>' . $declaration . '</DocumentNumber>
        <Phone>' . $auth["phone"] . '</Phone>
        </item></Documents>
        <Language>UA</Language>
        </methodProperties>
        </root>';
        }

        try {
            $ret = "<p>Результат пошуку за єкспресс-накладною " . $declaration . "</p>";
            $result = $this->_sendRequest($xml);
            $np = new SimpleXMLElement($result);
            if (!empty($np)) {
                if (empty($np->errors->item)) {
                    if ($np->data->item->StatusCode == 3) {
                        $ret .= '<b>Статус:</b> <strong>' . $np->data->item->Status . '</strong>';
                    } else {
                        $ret .= '<b>Маршрут:</b> ' . $np->data->item->CitySender . '->' . $np->data->item->CityRecipient;
                        $ret .= '<br><b>Адреса доставки:</b> ' . $np->data->item->WarehouseRecipient;
                        if ($np->data->item->StatusCode == 103) {
                            $ret .= '<b>Статус:</b> ' . $np->data->item->Status . ', ' . $np->data->item->UndeliveryReasonsSubtypeDescription;
                        } else {
                            if ($np->data->item->StatusCode == 9 || $np->data->item->StatusCode == 10 || $np->data->item->StatusCode == 106) {
                                $ret .= '<br><b>Статус:</b> <strong>' . $np->data->item->Status . ' ' . $np->data->item->RecipientDateTime . '</strong>';
                                if ($np->data->item->StatusCode == 10) {
                                    $ret .= '<br><br><b>Зворотня доставка:</b> <strong>' . $np->data->item->LastTransactionStatusGM . '</strong>';
                                }
                                if ($np->data->item->StatusCode == 106) {
                                    if ($np->data->item->RedeliveryNum > 0) {
                                        $ret .= '<br><br><b>Зворотня доставка:</b> <strong>Відправлення</strong>';
                                    }
                                }
                            } else {
                                if ($np->data->item->StatusCode == 7 || $np->data->item->StatusCode == 8) {
                                    $ret .= '<br><b>Статус:</b> <strong>' . $np->data->item->Status . ' ' . $np->data->item->ScheduledDeliveryDate . '</strong>';
                                } else {
                                    $ret .= '<br><br><b>Статус: </b>' . $np->data->item->Status;
                                }
                            }
                            //$ret .= '<br><br>'.$np->data->item->StatusCode;
                            if ($np->data->item->StatusCode == 4 || $np->data->item->StatusCode == 5 || $np->data->item->StatusCode == 6) {
                                $ret .= '<br><b>Орієнтовна дата доставки:</b> ' . $np->data->item->ScheduledDeliveryDate;
                            }
                        }
                    }
                } else { //if errors
                    $this->setLastError('api', $np->errors->item . ' ' . $declaration);
                    return "Результат пошуку за експрес-накладною " . $declaration . " - спробуйте повторити запит через деякий час хвилин.";
                }
            } //$np
        } catch (Exception $e) {
        }
        return $ret;
    }

    /**
     * Return response from API
     *
     * @param string $xml query
     *
     * @return string
     */
    private function _sendRequest(string $xml): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.novaposhta.ua/v2.0/xml/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function getSupportType(): array
    {
        return array('dt_novaposhta', 'dt_novaposhta_postomat');
    }
}
