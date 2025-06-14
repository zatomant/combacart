<?php

namespace Comba\Bundle\Modx\Tracking\Types;

use Comba\Core\Entity;
use Comba\Core\Logs;
use Exception;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Tracking class of NovaPoshta
 */
class TrackingNovaposhta extends TrackingNone
{
    protected string $title = 'Нова Пошта';
    protected string $url = 'https://novaposhta.ua/';
    protected string $urltracking = 'https://novaposhta.ua/tracking/?cargo_number=';

    public function getBarcodeInfo(string $declaration, string $seller): ?string
    {

        if (!parent::getBarcodeInfo($declaration, $seller)) {
            return null;
        }

        $auth = Entity::get3thAuth('NovaPoshta', $seller);

        $request = [
            "apiKey" => $auth['key'],
            "modelName" => "TrackingDocumentGeneral",
            "calledMethod" => "getStatusDocuments",
            "methodProperties" => [
                'Language' => 'UA',
                "Documents" => [
                    [
                        "DocumentNumber" => $declaration,
                        "Phone" => $auth['phone'] ?? ''
                    ],
                ]
            ]
        ];

        try {
            $ret = "<p>Результат пошуку за єкспресс-накладною " . $declaration . "</p>";
            $request = $this->sendRequest($request);
            $np = json_decode($request);

            if (json_last_error() !== JSON_ERROR_NONE || empty($np)) {
                return "Результат пошуку за експрес-накладною " . $declaration . " - спробуйте повторити запит через деякий час.";
            }

            if (!empty($np->errors)) {
                $this->setLastError('api', json_encode($np->errors, JSON_UNESCAPED_UNICODE));
                return "Результат пошуку за експрес-накладною " . $declaration . " - зверніть увагу на повідомлення про помилку";
            }

            foreach ($np->data as $item) {

                if ($item->StatusCode == 3) {
                    $ret .= '<b>Статус:</b> <strong>' . $item->Status . '</strong>';
                } else {
                    $ret .= '<b>Маршрут:</b> ' . $item->CitySender . '->' . $item->CityRecipient;
                    $ret .= '<br><b>Адреса доставки:</b> ' . $item->WarehouseRecipient;
                    if ($item->StatusCode == 103) {
                        $ret .= '<br><b>Статус:</b> ' . $item->Status . ', ' . $item->UndeliveryReasonsSubtypeDescription;
                    } else {
                        if ($item->StatusCode == 9 || $item->StatusCode == 10 || $item->StatusCode == 106) {
                            $ret .= '<br><b>Статус:</b> <strong>' . $item->Status . ' ' . $item->RecipientDateTime . '</strong>';
                            if ($item->StatusCode == 10) {
                                $ret .= '<br><br><b>Зворотня доставка:</b> <strong>' . $item->LastTransactionStatusGM . '</strong>';
                            }
                            if ($item->StatusCode == 106) {
                                if ($item->RedeliveryNum > 0) {
                                    $ret .= '<br><br><b>Зворотня доставка:</b> <strong>Відправлення</strong>';
                                }
                            }
                        } else {
                            if ($item->StatusCode == 7 || $item->StatusCode == 8) {
                                $ret .= '<br><b>Статус:</b> <strong>' . $item->Status . ' ' . $item->ScheduledDeliveryDate . '</strong>';

                                $dateObject = new \DateTime($item->DateFirstDayStorage);
                                $formattedDate = $dateObject->format('d.m.Y');

                                $ret .= '<br><b><span class="text-decoration-underline">Платне зберігання з : ' . $formattedDate . '</span></b>';
                            } else {
                                $ret .= '<br><br><b>Статус: </b>' . $item->Status;
                            }
                        }
                        //$ret .= '<br><br>'.$item->StatusCode;
                        if ($item->StatusCode == 4 || $item->StatusCode == 5 || $item->StatusCode == 6) {
                            $ret .= '<br><b>Орієнтовна дата доставки:</b> ' . $item->ScheduledDeliveryDate;
                        }
                    }
                }
            }
        } catch (Exception $e) {
        }
        return $ret;
    }

    /**
     * Return response from API
     *
     * @param string $data
     * @return string
     */
    private function sendRequest(array $data): ?string
    {
        $url = 'https://api.novaposhta.ua/v2.0/json/';

        // запит до віддаленого сервера
        $httpClient = Psr18ClientDiscovery::find();
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        // Якщо клієнт підтримує налаштування таймауту (наприклад, Guzzle)
        if (method_exists($httpClient, 'withOptions')) {
            $httpClient = $httpClient->withOptions([
                'timeout' => 45, // Загальний таймаут (секунди)
                'connect_timeout' => 5, // Таймаут на встановлення з'єднання
            ]);
        }

        $body = $streamFactory->createStream(json_encode($data));
        $request = $requestFactory->createRequest('POST', $url)
            ->withBody($body)
            ->withHeader('HTTP_VERSION', '2.0');

        try {
            $response = $httpClient->sendRequest($request);

            return $response->getBody()->getContents();

        } catch (ClientExceptionInterface $e) {
            (new Logs())->put('HTTP Request Error: ' . $e->getMessage(), LOG_ERR);
            return null;
        }
    }

    public function getSupportType(): array
    {
        return array('dt_novaposhta', 'dt_novaposhta_postomat');
    }
}
