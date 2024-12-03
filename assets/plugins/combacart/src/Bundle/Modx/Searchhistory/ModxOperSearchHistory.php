<?php

namespace Comba\Bundle\Modx\Searchhistory;

use Comba\Bundle\CombaApi\CombaApi;
use Comba\Bundle\Modx\ModxOper;
use Exception;
use RemoteIP;
use function Comba\Functions\sanitize;

class ModxOperSearchHistory extends ModxOper
{

    /**
     * @return void
     */
    function insert()
    {
        if ($this->IsBot()) return;

        $pIP = trim((new RemoteIP())->get_ip_address());

        $pHost = '';
        try {
            if (!empty($pIP) && filter_var($pIP, FILTER_VALIDATE_IP)) {
                $pHost = trim(gethostbyaddr($pIP));
            }
        } catch (Exception $e) {
        }

        $pPagecount = $pDuration = 0;
        if ($this->getOptions('duration') > 0) $pDuration = (int)$this->getOptions('duration');
        if ($this->getOptions('pagecount') > 0) $pPagecount = (int)$this->getOptions('pagecount');

        $pSearch = htmlspecialchars(sanitize($this->getOptions('search')));
        $pQuery = htmlspecialchars(filter_var($this->getOptions('query'), FILTER_SANITIZE_ENCODED));
        $pURL = htmlspecialchars(filter_var($this->getOptions('url'), FILTER_SANITIZE_URL));
        $pSite = htmlspecialchars(filter_var($this->getOptions('site'), FILTER_SANITIZE_URL));
        $pRefer = htmlspecialchars(filter_var($this->getOptions('refer'), FILTER_SANITIZE_URL));
        $pUserId = $this->User() ? htmlspecialchars(filter_var($this->User()->getId(), FILTER_SANITIZE_NUMBER_INT)) : null;
        if (empty($pRefer)) $pRefer = '';

        $ca = new CombaApi();

        $ca->request('SearchHistoryInsert',
            array(
                'Document' => array(
                    'ip' => $pIP,
                    'host' => $pHost,
                    'duration' => $pDuration,
                    'pagecount' => $pPagecount,
                    'search' => $pSearch,
                    'query' => $pQuery,
                    'site' => $pSite,
                    'url' => $pURL,
                    'refer' => $pRefer,
                    'userid' => $pUserId
                )
            )
        );
    }

}
