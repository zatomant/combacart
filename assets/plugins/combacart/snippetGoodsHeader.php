<?php

/**
 * GoodsHeader
 *
 * Prepare pages
 *
 * @category    snippet
 * @version     2.5
 * @package     evo
 * @internal    @modx_category Comba
 * @internal    @installset base
 * @author      zatomant
 * @lastupdate  22-02-2022
 */

$out 		= '<script src="/assets/js/jquery/jquery.min.js"></script>';
$outCart 	= '';

if (strpos($hide, 'cart') === false) {
    $params = array(
        'action' 	=> 'read',
        'docTpl' 	=> '@FILE:/chunk-Cart',
        'docEmptyTpl' 	=> '@FILE:/chunk-CartEmpty',
    );
    $outCart = $modx->runSnippet('CombaHelper', $params);
}

$out .= <<< EOD
<style>
.avail-0 {
    filter: grayscale(100%)
}
.avail-3 {
border-color: rgba(var(--bs-warning-rgb),var(--bs-border-opacity)) !important;
}
</style>
<div class="header-bottom shadow-sm">
	<ul class="nav justify-content-around align-items-center">
		<li class="nav-item order-sm-1 mx-auto d-none d-sm-block">
			<a href="/" class="text-center d-inline">[(site_name)]</a>
		</li>
		<li class="nav-item dropdown order-sm-4 shopcartplace">$outCart</li>
	</ul>
</div>
EOD;

return $out;
