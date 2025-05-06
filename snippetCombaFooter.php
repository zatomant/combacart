<?php
/**
 * CombaFooter
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

if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

$out = <<< EOD

<div class="modal" id="dlgSpinner" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="dlgSpinnerLabel" aria-hidden="true" style="z-index:9999">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body bg-light rounded">
                    <div class="d-flex align-items-center">
                        <span class="text-dark">[(__waitforresult)]</span>
                        <span class="ms-auto">
						    <div class="spinner-border text-success ms-auto" role="status" aria-hidden="true"></div>
					    </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/plugins/combacart/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

<script>
let observer;
let scriptLoadMap = [];

const loadScript = (FILE_URL, async = true, type = "text/javascript") => {
    return new Promise((resolve, reject) => {

        if ( !scriptLoadMap.includes(FILE_URL)) {
            // does not exist
            try {
                let body;
                let scriptEle;
                if (type === "text/javascript") {
                    scriptEle = document.createElement("script");
                    scriptEle.src = FILE_URL;
                    body = document.body;
                } else {
                    scriptEle = document.createElement("link");
                    scriptEle.media = 'all';
                    scriptEle.rel  = 'stylesheet';
                    scriptEle.href = FILE_URL;
                    body = document.getElementsByTagName('head')[0];
                }
                scriptEle.type = type;
                scriptEle.async = async;

                scriptEle.addEventListener("load", (ev) => {
                    scriptLoadMap.push(FILE_URL);
                    resolve({status: true});
                });

                scriptEle.addEventListener("error", (ev) => {
                    reject({
                        status: false,
                        message: `Failed to load the script ＄{FILE_URL}`
                    });
                });

                body.appendChild(scriptEle);
            } catch (error) {
                reject(error);
            }
        }
    });
};

loadScript( "/assets/plugins/combacart/vendor/twbs/bootstrap/dist/css/bootstrap.min.css?v523", true, "text/css");
loadScript( "/assets/plugins/combacart/assets/js/venobox/venobox.min.css", true, "text/css");
loadScript( "/assets/plugins/combacart/vendor/twbs/bootstrap-icons/font/bootstrap-icons.css?v1103", true, "text/css");
loadScript( "/assets/plugins/combacart/assets/css/animate/animate.min.css", true, "text/css");

    function counter_oper(elem,oper){
        let effect = jQuery(elem).closest(".boxcounter").find(".counter");
        let qty = effect.val();
        if (!isFinite(qty)) qty = 0;
        if (oper){
            if( !isNaN( qty )) effect.val( ++qty );
        } else {
            if( !isNaN( qty ) && qty > 0 ){
                effect.val( --qty )
            } else {
                effect.val(1);
            }
        }
        if (effect.data("recount")){
            effect.change();
        }
        return false;
    }

    function _posting(data){
        let url = window.location.href;
        $.ajax(
            {
            url: url,
            data: data,
            type:'POST',
            timeout: 10000,
            success:function (data) {
                    ToastHide();
                    jQuery(".specspinner").addClass("invisible");
                    if (data){
                        if (data && data.length > 20){
                            jQuery( "#spec" ).replaceWith( data );
                            let n = data.search("emptyCart");
                            if (n>1) jQuery( "#cfcd" ).remove();
                        }
                    }
            },
            error:function () {
                    ToastHide();
                    jQuery(".specspinner").addClass("invisible");
                    showError('[(__error_reload)]');
            }
        });
    }

    function checkImageExists(url, callback) {
        if (!url) return callback(false);
        let img = new Image();
        img.onload = () => callback(true);
        img.onerror = () => callback(false);
        img.src = url;
    }

document.addEventListener("DOMContentLoaded", function(event) {

    jQuery(document).ready(function (jQuery) {

        jQuery.cachedScript = function( url, options ) {
            options = $.extend( options || {}, {
                dataType: "script",
                cache: true,
                url: url
            });
            return jQuery.ajax( options );
        };

        jQuery(document.body).on("click","button[name=button-minus]", function(){
            counter_oper(this);
        });
        jQuery(document.body).on("click","button[name=button-plus]", function(){
            counter_oper(this,1);
        });
        jQuery(document.body).on("click change",".deletebutton, .counter",function(event){
            let specid = jQuery(this).data("specid");
            if (jQuery( this ).hasClass( "deletebutton" )){
                showConfirm({
                    txt: "[(__action_delete_goods)]?",
                    callback: function (e) {
                        if (e) {
                            showSpinner("[(__action_deleting)]");
                            jQuery(".specspinner").removeClass("invisible");
                            _posting({action:"ch_delete", specid:specid});
                        }
                    }
                });
            }
            if (jQuery( this ).hasClass( "counter" )){
                if (event.type === "click") return false;
                let specid = jQuery(this).closest(".orderspec").data("specid");
                let amount = jQuery(this).val();
                if (!amount || amount <1) {
                    jQuery(this).val(1);
                    return;
                }
                showSpinner("[(__action_change_amount)]");
                jQuery(".specspinner").removeClass("invisible");
                event.stopPropagation();
                _posting({action:"ch_update", specid:specid, count:amount});
            }
        });

        jQuery(document.body).on("click",".buybutton",function( event ) {
            event.preventDefault();
            let form = jQuery(this).closest("form");
            let url = form.attr( "action" );
            let formData = form.serializeArray();
            formData.push({ name: "action", value: "ch_insert"});
            showSpinner("[(__action_adding)]");
            let posting = $.post( url, formData);
            posting.done(function( data ) {
                ToastHide();
                if (data && data.length > 20){
                    jQuery( ".shopcartplace" ).html( data );
                }
            });
        });

        loadScript( "/assets/plugins/combacart/src/Themes/templates/dlg.js?v32");
        loadScript( "/assets/plugins/combacart/src/Themes/templates/serialize_v4.js?v30");
        loadScript( "/assets/plugins/combacart/assets/js/bootbox/bootbox.min.js");
        $.cachedScript( "/assets/plugins/combacart/assets/js/venobox/venobox.min.js").done(function( script, textStatus ) {
            new VenoBox({numeration:true,navTouch:true,titlePosition:'bottom',toolsBackground:'var(--gray-dark)'});
        });
        $.cachedScript( "/assets/plugins/combacart/assets/js/lozad/lozad.min.js").done(function( script, textStatus ) {
            observer = lozad('.lazy', {
                rootMargin: '10px 0px',
                threshold: 0.1,
                enableAutoReload: true
            });
            observer.observe();
            jQuery(document).ajaxComplete(function() {
                observer.observe();
            });
        });
    });

    document.querySelectorAll('a.venobox').forEach(link => {
        const originalHref = link.getAttribute("href");
        const source = link.querySelector("source");
        const img = link.querySelector("img");

        let urlToCheck = null;

        // Якщо є source і в ньому є атрибут type, перевіряємо, чи це image/webp
        if (source && source.getAttribute("type") === "image/webp") {
            urlToCheck = source.getAttribute("data-fullsize");
        }

        // Якщо нема webp, перевіряємо на jpg чи інший формат у img
        if (!urlToCheck && img) {
            urlToCheck = img.getAttribute("data-fullsize");
        }

        // Якщо знайдений правильний URL, перевіряємо його на існування
        if (urlToCheck) {
            checkImageExists(urlToCheck, function(exists) {
                if (exists) {
                    link.setAttribute("href", urlToCheck);
                }
            });
        }
    });
});
</script>
EOD;

return $out;
