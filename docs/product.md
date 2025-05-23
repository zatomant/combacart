# Товари #

До шаблону товара (Сторінка товару) ви можете додавати свої TV, сніпети, плагіни, змінювати верстку, і якщо ви не прибираете Загальні TV з шаблону - це не буде впливає на роботу CombaCart.  

Демонстративний шаблон Сторінка товару містить мінімальний набір компонентів і лише показує принцип роботи з Товаром та Кошиком покупця.  
Досить просто зробити сторінки з переліком товарів, наприклад використавши DocLister та eFiter. Ці додатки встановлюються через extras.


## Важливо ##

### Загальні TV ###

До шаблона goods_tmplt мають бути прив'язані TV:
- goods_avail
- goods_code
- goods_price
- goods_price_old
- goods_weight
- goods_isnewproduct
- goods_isondemand
- goods_seller
- goods_inbalances
- goods_images
- goods_goods


### Артикул ###

Код товару (артикул) має бути унікальним в контексті сторінки (документа).  
Тобто, в Товарі що містить варіанти (в TV goods_goods), усі артикули (goods_code) мають відрізнятись.  
Наприклад,  
Літак, артикул 1111111  
Літак з крилами, артикул 1111112  
Літак з пілотом, артикул 1111115  


### Ідентифікатор товару ###

Майже усі дії з Товаром в Кошику мають містити ідентифікатор товару. Він поєднує ідентифікатор сторінки та артикул Товару.  
Для отримання ідентифікатора скористайтесь функцією ```CombaFunctions? &fnct=`goodslguid```  


### Поля форми ###

При створенні сторінок каталогу, розділів з декільками товарами, враховуйте цей мінімальний набір властивостей.

**Мінімальна кількість полів для додавання Товару в Кошик:**  
- ідентифікатор сторінки
- ідентифікатор Товару  

```
<form>
<input type="hidden" name="goodsid" value="[*id*]">
<input type="hidden" name="goodslguid" value="[[CombaFunctions? &fnct=`goodslguid` &string=`[*id*]_[*goods_code*]`]]">
<button type="submit" name="submit" class="btn submit buybutton">В кошик</button>
</form>
```