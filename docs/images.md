## Загальна інформація ##

Для frontend завжди повертається шлях до сформованого, з урахуванням налаштувань пресетів, кешованого зображення з assets/cache/.
Перелік дефолтних пресетів в /Config/imagepresets.php

### Змінні та Методи ###
Entity::get('WATERMARK_TEXT') текст ватермарка (за замовчувнням адреса веб сайту)  
Метод getImage() класу Comba\Bundle\Modx\ModxImage  
Сніпет [[CombaFunctions? &fnct=`GetImage`]]  

Якщо встановлено multiTV, запуск сніпета без параметрів повертає шлях до першого зображення.  
 

### Приклади ###

Отримати шлях до першого зображення (поточний ідентифікатор сторінки(Товару))  
```[[CombaFunctions? &fnct=`GetImage`]]```

Отримати шлях до першого зображення сторінки з id = 4   
```[[CombaFunctions? &fnct=`GetImage` &id=`4`]]```

Отримати шлях до зображення що знаходиться в  /assets/images/baner/1.jpg  
```[[CombaFunctions? &fnct=`GetImage` &src=`/assets/images/baner/1.jpg`]]```

Отримати шлях до зображення з урахуванням налаштувань пресету goods-tnx  
```[[CombaFunctions? &fnct=`GetImage` &src=`{{ spec.docspec_product_photo }}` &preset=`goods-tnx`]```

Отримати шлях до зображення з урахуванням налаштувань пресету goods-tnx та обов'язково конвертувати його в webp формат  
```[[CombaFunctions? &fnct=`GetImage` &src=`{{ spec.docspec_product_photo }}` &webp=`1` &preset=`goods-tnx`]]```

Отримати шлях до зображення з урахуванням налаштувань пресету image-max-webp (параметр webp=1 присутній в налаштувані пресету)  
```[[CombaFunctions? &fnct=`GetImage` &src=`{{ spec.docspec_product_photo }}` &preset=`image-max-webp`]]```