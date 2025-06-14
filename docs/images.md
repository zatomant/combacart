## Загальна інформація ##

Для frontend завжди повертається шлях до сформованого, з урахуванням налаштувань пресетів, кешованого зображення з assets/cache/.
Перелік дефолтних пресетів в /Config/imagepresets.php

### Змінні та Методи ###
Entity::get('WATERMARK_TEXT') налаштування ватермарка
Entity::getData('Imagepresets') налаштування пресетів
Метод get() класу Comba\Bundle\Modx\ModxImage  
Сніпет [[CombaFunctions? &fnct=`GetImage`]]  

Якщо встановлено multiTV, запуск сніпета без заначення параметра n повертає шлях до першого зображення.  
При передачі параметра id параметр imgratio можна пропустити. 

### Параметри ###

Обов'язкові параметри:  
src - шлях до зображення  
id - ідентифікатор сторінки. при вказанні цього параметру imgratio підтягується з налаштувань ресурсу з id.    

Опціональні параметри:  
preset - назва пресету з предналаштованими параметрами обробника  
phpthumb - рядок налаштувань обробника в форматі phpThumb (якщо не хочемо використовувати preset)  
imgratio - рядок налаштувань пропорцій в форматі phpThumb  
n - номер зображення в списку (якщо використовуються списки зображень multiTV)  
flags - може містити параметри розділені через кому:  
    src - повернути ім'я файлу зображення без обробки  
    webp - перетворити зображення в формат в webp (ігнорує формат завданий в preset)  
    forced - перезаписати вихідне зображення (видаляє та створює нове) в кеші  
    lazy - додає до результату рядок ' src="data:image/png......"  з заглушкою в форматі base64  
    sof - додає до результату height="" width="" з розмірами вихідного зображеня

### Приклади ###

Шлях до першого зображення сторінки з ідентифікатором 4   
```[[CombaFunctions? &fnct=`GetImage` &id=`4`]]```

Шлях до п'ятого зображення сторінки з id = 4   
```[[CombaFunctions? &fnct=`GetImage` &id=`4` &n=`5`]]```

Шлях до зображення що знаходиться в /assets/images/baner/1.jpg  
```[[CombaFunctions? &fnct=`GetImage` &src=`/assets/images/baner/1.jpg`]]```

Шлях до зображення з урахуванням налаштувань пресету goods-tnx  
```[[CombaFunctions? &fnct=`GetImage` &src=`{{ spec.docspec_product_photo }}` &preset=`goods-tnx`]```

Шлях до зображення з урахуванням налаштувань пресету goods-tnx і конвертуванням його в webp формат  
```[[CombaFunctions? &fnct=`GetImage` &src=`{{ spec.docspec_product_photo }}` &preset=`goods-tnx` &flags=`webp,forced`]]```

Шлях до зображення з урахуванням налаштувань пресету image-max-webp   
```[[CombaFunctions? &fnct=`GetImage` &src=`{{ spec.docspec_product_photo }}` &preset=`image-max-webp` &flags=`forced`]]```