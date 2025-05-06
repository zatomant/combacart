<?php

/**
 * Цей метод оплати жодно разу не тестувався.
 * Потрібно отримати токен що б протестувати це все.
 * поки на паузі
 *
 * і взагалі, api liqpay виявилось набагато краще за монобанк
 */

namespace Comba\Bundle\Modx\Payment\Types\Monobank;

use Comba\Bundle\Modx\ModxCart;
use Comba\Bundle\Modx\ModxOptions;
use Comba\Core\Entity;
use Comba\Core\Cache;
use MonoPay\Client;
use MonoPay\Payment;
use MonoPay\Webhook;
use function Comba\Functions\filterArrayRecursive;

class Monobank extends ModxOptions
{
    private string $token;
    private string $provider;

    public function setProvider(string $provider, array $data): Monobank
    {
        $this->provider = $provider;
        $this->setAuth($data);
        return $this;
    }

    public function setAuth(array $auth): Monobank
    {
        $this->token = $auth['token'];
        return $this;
    }

    public function getContent(array $dataset): ?array
    {
        $responseData = [];

        if (empty($this->token)) {
            $this->log('ERROR', 'Не визначено токен для провайдера ' . $this->provider);
        } else {
            try {
                $monoClient = new Client($this->token,
                    [
                        'X-Cms' => Entity::get('NAME'),
                        'X-Cms-Version' => Entity::get('VERSION')
                    ]
                );

                $monoPayment = new Payment($monoClient);

                //створення платежу
                $responseData = $monoPayment->create(
                    $dataset['doc_paysum'],
                    [
                        //деталі оплати
                        'merchantPaymInfo' => [
                            'reference' => base64_encode(
                                json_encode(
                                    [
                                        "uid" => $dataset['doc_uid'],
                                        "provider" => $this->provider,
                                        "seller" => $dataset['doc_seller']
                                    ]
                                )
                            ),
                            'destination' => 'Замовлення №' . $dataset['doc_number']
                        ],
                        'redirectUrl' => Entity::get('SERVER_HOST') . '/' . Entity::get('PAGE_TRACKING') . '?' . $dataset['doc_uid'],
                        'callbackUrl' => Entity::get('SERVER_HOST') . '/' . Entity::get('PAGE_PAYMENT_CALLBACK') . '?' . 'Monobank',
                        'validity' => 3600 * 24 * 7, //строк дії в секундах, за замовчуванням рахунок перестає бути дійсним через 24 години
                        'paymentType' => 'debit', //debit | hold. Тип операції. Для значення hold термін складає 9 днів. Якщо через 9 днів холд не буде фіналізовано — він скасовується
                        'ccy' => 980
                    ]
                );
            } catch (\Throwable $throwable) {
                $this->log('ERROR', $throwable->getMessage());
            }
        }

        if (isset($responseData['pageUrl'])) {
            $param['pageUrl'] = $responseData['pageUrl'];
        } else {
            $param['error'] = $responseData['errorDescription'] ?? 'Відсутні налаштування для оплати через monobank';
            $this->log('ERROR', $param['error']);
        }

        return [
            'error' => $param['error'],
            'form' => $this->cnb_form($param)
        ];
    }

    public function cnb_form($params): ?string
    {
        return $params['pageUrl'] ?? null;
    }

    public function fetchCallback(array $paymentstatus)
    {

        if (empty($paymentstatus)) {
            exit;
        }

        $info = json_decode(base64_decode($paymentstatus['reference']), true);

        try {
            $p_auth = Entity::get3thAuth($info['provider'], $info['seller']);
            if (empty($p_auth)) {
                exit;
            }

            $this->setAuth($p_auth);
            $monoClient = new Client($this->token);

            if ($cacheData = (new Cache($info['provider'] . $this->token))->setLogLevel($this->getLogLevel())->get()) {
                // отримання публічного ключа з кешу
                $publicKey = $cacheData;
            } else {
                // отримання публічного ключа з сервера
                $publicKey = $monoClient->getPublicKey();

                if (!empty($publicKey)) {
                    (new Cache($info['provider'] . $this->token))
                        ->setLogLevel($this->getLogLevel())
                        ->setLifetime($this->cacheLifetime)
                        ->set($publicKey);
                }
            }

            $monoWebhook = new Webhook($monoClient, $publicKey, $_SERVER['HTTP_X_SIGN']);

            if (!$monoWebhook->verify(json_encode($paymentstatus))) {
                // Видаляємо публічний ключ тому що не пройшла валідація. можливо ключ протух.
                (new Cache($info['provider'] . $this->token))
                    ->setLogLevel($this->getLogLevel())
                    ->setLifetime(0)
                    ->set('0');
                exit;
            }
        } catch (\Throwable $throwable) {
            $this->log('ERROR', $throwable->getMessage());
        }

        $this->log('DEBUG', $paymentstatus);

        $uid = $info['uid'];
        if ($uid <= 0) {
            exit;
        }

        $status = $paymentstatus['status'];

        if ($status == 'success') {
            (new ModxCart($this))
                ->setID($uid)
                ->notifyPayment(
                    array_merge(
                        ['manager' => $info['provider'] . ' (callback)'],
                        filterArrayRecursive(
                            $paymentstatus,
                            ['invoiceId', 'modifiedDate', 'status', 'maskedPan', 'paymentMethod', 'amount', 'ccy', 'bank'],
                            ['cancelList', 'walletData', 'tipsInfo'])
                    )
                );
        }
    }

}
