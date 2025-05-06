<?php
/**
 * OrderPaymentStatus
 *
 * callback from online payment server for orders
 *
 * @category    snippet
 * @version     2.6
 * @package     evo
 * @internal    @modx_category Comba
 * @author      zatomant
 * @lastupdate  22-02-2022
 */

namespace Comba\Bundle\Modx\Payment;

use Comba;
use Comba\Bundle\Modx\ModxOptions;
use Comba\Core\Entity;
use function Comba\Functions\safeHTML;

class PaymentCallback extends ModxOptions
{

    public function render()
    {

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $paymentstatus = $_POST;

        $queryParams = parse_url(safeHTML($requestUri), PHP_URL_QUERY);
        $this->log('DEBUG', $queryParams);

        if (strpos($requestUri, Entity::get('PAGE_PAYMENT_CALLBACK') . '?') !== false && !empty($paymentstatus) && !empty($queryParams) && !is_array($queryParams)) {

            if (preg_match('/^[a-zA-Z0-9_-]+$/', $queryParams)) {
                $directory = __DIR__ . '/Types';
                $filePath = $directory . DIRECTORY_SEPARATOR . $queryParams . DIRECTORY_SEPARATOR . $queryParams . '.php';

                if (file_exists($filePath) && is_file($filePath)) {

                    $p_class = '\Comba\Bundle\Modx\Payment\Types\\' . $queryParams . '\\' . $queryParams;
                    try {
                        (new $p_class($this))->fetchCallback($paymentstatus);
                        exit;
                    } catch (\Throwable $e) {
                        $this->log('ERROR', 'FATAL no class ' . $p_class);
                    }
                } else {
                    $this->log('ERROR', 'FATAL no file ' . $filePath);
                }
            }
        }
    }

}

