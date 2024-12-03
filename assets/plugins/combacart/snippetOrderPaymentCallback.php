<?php
/**
 * OrderPaymentStatus
 *
 * callback from online payment server for orders
 *
 * @category    snippet
 * @version     2.5
 * @package     evo
 * @internal    @modx_category Comba
 * @author      zatomant
 * @lastupdate  22-02-2022
 */


use Comba\Core\Logs;
use function Comba\Functions\safeHTML;

if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

require_once __DIR__ . '/autoload.php';

$lg = new Logs('paymentstatus');

$queryParams = parse_url(safeHTML($requestUri), PHP_URL_QUERY);
$lg->save($queryParams);

if (strpos($requestUri, COMBAMODX_PAGE_PAYMENT_CALLBACK . '?') !== false && !empty($paymentstatus) && !empty($queryParams) && !is_array($queryParams)) {

    if (preg_match('/^[a-zA-Z0-9_-]+$/', $queryParams)) {
        $directory = dirname(__FILE__) . COMBAMODX_PATH_SRC . '/Bundle/Payments';
        $filePath = $directory . DIRECTORY_SEPARATOR . $queryParams . DIRECTORY_SEPARATOR . $queryParams . '.php';

        if (file_exists($filePath) && is_file($filePath)) {

            $p_class = '\Comba\Bundle\Payments\\' . $queryParams . '\\' . $queryParams;
            try {
                (new $p_class($modx))->fetchCallback($paymentstatus);
                exit;
            } catch (\Throwable $e) {
                $lg->save('FATAL no class ' . $p_class);
            }
        } else {
            $lg->save('FATAL no file ' . $filePath);
        }
    }
}

