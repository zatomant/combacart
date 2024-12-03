 /**
 * goods_tmplt
 *
 * Goods template
 *
 * @category        templates
 * @name            goods_tmplt
 * @desctiption     Goods template
 * @internal        @type 1
 * @internal        @lock_template 0
 * @internal        @modx_category Comba
 * @internal        @installset base
 */
 <!doctype html>
 <html {{html_lang}}>
 <head>
     <title>[*pagetitle*]</title>
     <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
     <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
     <meta name="keywords" content="">
     <meta name="description" content="[*description*]">
     <meta name="robots" content="index,follow">
     <base href="[(site_url)][(_root)]">
 </head>
 <body>
 <div class="wrapper">

     [!GoodsHeader!]
     <div class="container">
         <div class="row">
             <div class="col">
                 <section>
                     <h3>[*longtitle*]</h3>
                     <p>[*description*]</p>
                     [[GoodsFunctions? &fnct=`showSeller` &seller=`[*goods_seller*]`]]
                     <form>
                         <input type="hidden" name="goodsid" value="[*id*]">
                         <input type="hidden" name="goodslguid" value="[[GoodsFunctions? &fnct=`goodslguid` &string=`[*goods_code*]`]]">
                         <picture>
                             <source srcset="[[GetImage? &phpthumb=`webp=1` &preset=`page-goods-2`]]" media="(max-width: 350.99px)" type="image/webp" />
                             <img loading="lazy" data-src="/[[GetImage? &phpthumb=`webp=1` &preset=`page-goods-3` &oper=`lazy`]]" class="lazy img-fluid pe-1 rounded-lg">
                         </picture>

                         <p class="h5 fw-bold mb-3">
                         [[GoodsFunctions? &fnct=`is` &cond=`[*goods_price*];[*goods_avail*]` &then=`
                          <span class="text-info fw-light me-1"><s class="goodspriceold">[*goods_price_old*]</s></span>
                          <span content="[*goods_price*]" class="goodsprice pe-1">[*goods_price*]</span>
                          <span>[+currency+]</span>
                       `]]
                         </p>

                         [[GoodsFunctions? &fnct=`buttonbuy` &avail=`[*goods_avail*]` &old=`[*goods_price_old*]` &ondem=`[*goods_isondemand*]` &class=`mainbuybutton col-md-6`]]
                         <div class="my-2 small">[(__goods_code_title)]: <span id="gdscode-[*id*]" class="goodscode">[*goods_code*]</span></div>
                     </form>
                 </section>

                 [*#content*]
             </div>
         </div>
     </div>
 </div>

 [[GoodsFooter]]

 </body>
 </html>