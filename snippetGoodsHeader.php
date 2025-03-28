<?php

/**
 * GoodsHeader
 *
 * Prepare pages
 *
 * @category    snippet
 * @version     2.6
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
<script>
document.addEventListener("DOMContentLoaded", function(event) {

const pictures = document.querySelectorAll("picture");

for (const picture of pictures) {
    const image = picture.querySelector("img");

    if (image && !image.classList.contains("cart-img")) {
        image.setAttribute("loading", "eager");
        image.classList.remove("lazy");
        //image.removeAttribute("height");
        break;
    }
}
jQuery(document).ready(function (jQuery) {
    jQuery.cachedScript = function( url, options ) {
        options = $.extend( options || {}, {
            dataType: "script",
 			cache: true,
 			url: url
        });
        return jQuery.ajax( options );
    };
})
});
</script>
EOD;

return $out;
