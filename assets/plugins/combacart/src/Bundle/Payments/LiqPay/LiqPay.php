<?php

namespace Comba\Bundle\Payments\Liqpay;

use Comba\Bundle\Modx\ModxCart;
use Comba\Bundle\Modx\ModxOptions;
use Comba\Core\Logs;
use function Comba\Functions\filterArrayRecursive;

class LiqPay extends ModxOptions
{

    private string $public_key;
    private string $private_key;

    public function setAuth(array $auth): LiqPay
    {
        $this->public_key = $auth['public_key'];
        $this->private_key = $auth['private_key'];

        return $this;
    }

    public function getContent(array $dataset): ?string
    {
        $param = array(
            'version' => '3',
            'action' => 'pay',
            'amount' => $dataset['doc_paysum'],
            'currency' => $dataset['doc_currency'] ?: 'UAH',
            'description' => 'Замовлення №' . $dataset['doc_number'],
            'order_id' => md5(rand(0, 10000)),
            'info' => json_encode(["uid" => $dataset['doc_uid'], "provider" => "LiqPay"]),
            'result_url' => COMBAMODX_SERVER_HOST . '/' . COMBAMODX_PAGE_TRACKING . '?' . $dataset['doc_uid'],
            'server_url' => COMBAMODX_SERVER_HOST . '/' . COMBAMODX_PAGE_PAYMENT_CALLBACK . '?' . 'LiqPay',
        );

        $liqpay = new \LiqPay($this->public_key, $this->private_key);
        return $liqpay->cnb_form($param);
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
            (new Logs())->save($parsed_data);
        }

        $received_public_key = $parsed_data['public_key'];
        $status = $parsed_data['status'];
        $info = json_decode($parsed_data['info'], true);

        $uid = $info['uid'];
        if ($uid <= 0) {
            exit;
        }

        $p_auth = get3thAuth($info['provider']);
        $private_key = $p_auth['private_key'];
        $public_key = $p_auth['public_key'];

        $generated_signature = base64_encode(sha1($private_key . $data . $private_key, 1));

        if ($received_signature != $generated_signature || $public_key != $received_public_key) {
            exit;
        }

        if ($status == 'success') {
            (new ModxCart($this->getModx()))
                ->setID($uid)
                ->notifyPayment(
                    array_merge(
                        ['manager' => $info['provider'] . ' (callback)'],
                        filterArrayRecursive($parsed_data, ['payment_id', 'create_date','status', 'paytype', 'amount', 'currency', 'sender_phone', 'sender_first_name', 'sender_last_name'])
                    )
                );
        }

    }
}