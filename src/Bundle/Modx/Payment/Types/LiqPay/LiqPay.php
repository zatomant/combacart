<?php

namespace Comba\Bundle\Modx\Payment\Types\LiqPay;

use Comba\Bundle\Modx\ModxCart;
use Comba\Bundle\Modx\ModxOptions;
use Comba\Core\Entity;
use function Comba\Functions\filterArrayRecursive;

class LiqPay extends ModxOptions
{

    private string $provider;
    private string $public_key;
    private string $private_key;

    public function setProvider(string $provider, array $data): LiqPay
    {
        $this->provider = $provider;
        $this->setAuth($data);

        return $this;
    }

    public function setAuth(array $auth): LiqPay
    {
        $this->public_key = $auth['public_key'];
        $this->private_key = $auth['private_key'];

        return $this;
    }

    public function getContent(array $dataset): ?array
    {
        $param = [
            'version' => '3',
            'action' => 'pay',
            'amount' => $dataset['doc_paysum'],
            'currency' => $dataset['doc_currency'] ?: 'UAH',
            'description' => 'Замовлення №' . $dataset['doc_number'],
            'order_id' => md5(rand(0, 10000)),
            'info' => json_encode(
                [
                    "uid" => $dataset['doc_uid'],
                    "provider" => $this->provider,
                    "seller" => $dataset['doc_seller']
                ]
            ),
            'result_url' => Entity::get('SERVER_HOST') . '/' . Entity::get('PAGE_TRACKING') . '?' . $dataset['doc_uid'],
            'server_url' => Entity::get('SERVER_HOST') . '/' . Entity::get('PAGE_PAYMENT_CALLBACK') . '?' . 'LiqPay',
        ];

        $liqpay = new \LiqPay($this->public_key, $this->private_key);
        $_form = $liqpay->cnb_form($param);

        return [
            'error' => $param['error'],
            'form' => $_form
        ];
    }

    public function fetchCallback(array $paymentstatus)
    {
        $success =
            isset($paymentstatus['data']) &&
            isset($paymentstatus['signature']);

        if (!$success) {
            exit;
        }

        $data = $paymentstatus['data'];
        $parsed_data = json_decode(base64_decode($data), true);
        $received_signature = $paymentstatus['signature'];

        if (!empty($parsed_data)) {
            $this->log('DEBUG', $parsed_data);
        }

        $received_public_key = $parsed_data['public_key'];
        $info = json_decode($parsed_data['info'], true);

        $uid = $info['uid'];
        if ($uid <= 0) {
            exit;
        }

        $p_auth = Entity::get3thAuth($info['provider'], $info['seller']);
        $private_key = $p_auth['private_key'];
        $public_key = $p_auth['public_key'];

        $generated_signature = base64_encode(sha1($private_key . $data . $private_key, 1));

        if ($received_signature != $generated_signature || $public_key != $received_public_key) {
            exit;
        }

        $status = $parsed_data['status'];

        if ($status == 'success') {
            (new ModxCart($this))
                ->setID($uid)
                ->notifyPayment(
                    array_merge(
                        ['manager' => $info['provider'] . ' (callback)'],
                        filterArrayRecursive($parsed_data, ['payment_id', 'create_date', 'status', 'paytype', 'amount', 'currency', 'sender_phone', 'sender_first_name', 'sender_last_name'])
                    )
                );
        }

    }
}
