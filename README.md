## Мінімальна технічна конфігурація ##

PHP 7.4 та вище

Composer 

EVO 1.4+ або EVO 3+ (Evolution CMS)


## Можливості ##
За допомогою CombaCart можна створити повноцінний інтернет-магазин чи маркетплейс (декілька Продавців на одному сайті) на базі Evolution CMS 1.4+ чи EVO 3.2+.

Для Покупця:
- перегляд, додавання та редагування Товарів у Кошику, та подальше оформлення Кошика в Замовлення покупця.
- сторінка Відстеження та Оплата замовлення.
- онлайн оплата та отримання callback відповіді через LiqPay.
- перегляд історії Замовлень (персональний кабінет). Реєстрацію та авторизацію користувача робіть засобами Evolution CMS або через спеціалізований плагін, наприклад HybridAuth.
- підтримка багатомовності: Українська, Англійська.

Для Менеджера:
- керування Замовленнями покупців на окремій сторінці
- перегляд, редагування, зміна статусів обробки та друк Замовлення
- відправлення текстових повідомлень через пошту, смс, текст для месенджерів.

Для Адміністратора:
- автоматичне прибирання Товару з Кошиків, якщо з товару знято Доступен до замовлення ( редагування сторінки Товару в адмінці (evo))
- для кожного товару можна задати свого Продавця
- Продавець це окрема юридична особа або ФОП зі своїми налаштуваннями.
- при оформленні Кошика автоматично сформуються декілька Замовлень якщо Товари у кошику від різних Продавців (опціонально) 



## Встановлення ##

Інсталюйте CombaCart:
- завантажте архів з CombaCart з [github.com](https://github.com/zatomant/combacart) та на сторінці Extras перейдіть до  "Install by file", виберіть архівний файл і натисніть "Install".
  
або

- оновіть CombaCart новими файлами з [github.com](https://github.com/zatomant/combacart) шляхом розпакування архіву та заміни файлів в /assets/plugins .
  
**Обов'язково після встановлення CombaCart** виконайте в консолі оновлення компонентів вказаних в composer.json  
```
cd /assets/plugins/combacart

composer update
```

файл composer.json містить перелік компонентів що використовується в CombaCart.
ви можете прибрати зайві на ваш розсуд залежності та модифікувати шаблони під ваші потреби.


## Налаштування ##

**файл /src/Config/config.php**
* містить основні шляхи та глобальні константи для роботи плагіну. не змінюйте їх без нагальної потреби.

**файл /assets/custom/Config/config_custom.php**
* містить ваші дані аутентифікації до стороніх сервисів, наприклад:
  - НоваПошта
  - LiqPay
  -  смс провайдер
  -  та інші


**файл /src/Bundle/BuildInServer/Server.php**
* містить методи та масиви з даними, і саме ці дані редагуйте під свої потреби:
1. метод marketplace()
   - повертає загальні налаштування інтернет магазину/маркетплейсу (формальна різниця між ІМ та М: ІМ може мати одного Продавця, а маркетплейс може зберігати декілька Продавців)

2. метод sellers()
   - повертає дані по Продавцях (публічні дані)
   - Продавці крім основних параметрів містять посилання на Отримувачів оплат

3. метод payee()
   - повертає дані Отримувачів оплат
   - Отримувачі оплат це юридичнф особи чи ФОПм з типами оплат які вони підтримують

4. метод delivery()
   - повертає перелік типів доставок

5. метод payment()
   - повертає перелік типів оплат



## Перші кроки після закінчення інсталяції та наолаштувань ##

1. При інсталяції через Extras будуть створені необхідні елементи, а саме :

   * шаблон для сторінки товару goods_tmplt
   * шаблон для сторінки оформлення замовлення checkout_tmplt

   * tv
     - goods_avail ознака чи доступний товар до замовлення
     - goods_code актикул товару (sku)
     - goods_price ціна товару
     - goods_price_old стара ціна товару
     - goods_weight вага товару
     - goods_isnewproduct ознака "новий товар"
     - goods_isondemand ознака "товар під замовлення"
     - goods_seller Продавець товару
     - goods_inbalances ознака залежності товару від залишків
     - goods_images містить перелік зображень.
      - для frontend завжди повертається шлях до зображення з assets/cache
      - якщо використовується multiTV, запуск сніпету [[GoodsImage]] без параметрів повертає шлях до першого зображення
      - якщо запустити сніпет ось так [[GoodsImage? &imgsrc=`[*goods_images*]`]] поверне шлях [*goods_images*] враховуючі кеш та інші налаштування, наприклад розміри.
   
     * goods_goods містить перелік видів товару (опціонально), використовує multiTV

   * сніпети, в тому числі:
      - snippetOrderPay для показу сторінки з варіантами оплат
      - snippetOrderTracking для показу статусу обробки замовлення

3. До шаблона goods_tmplt привяжіть TV goods_avail, goods_code, goods_price, goods_price_old, goods_weight, goods_isnewproduct, goods_isondemand, goods_seller

4. Створіть нову сторінку (документ), задайте їй шаблон goods_tmplt. Це буде ваш перший товар.
   Код товару (артикул) має бути унікальним в контексті сторінки (документа).

5. Створіть сторінку з псевдонімом (alias) checkout та задайте їй шаблон checkout_tmplt . Це буде сторінка оформлення замовлення.
   якщо використовуєте інший псевдонім то внесіть його в константу COMBAMODX_PAGE_CHECKOUT в файлі /src/Config/config.php

6. Створіть сторінку з псевдонімом (alias) tnx на яку буде перенаправлено користувача після створення замовлення.
   якщо використовуєте інший псевдонім внесіть його в константу COMBAMODX_PAGE_TNX в файлі /src/Config/config.php
   у разі відсутності такої сторінки буде перехвачено OnPageNotFound та відображенно текст з /src/Bundle/Modx/Cart/templates/chunk-CheckoutTnx

7. Створіть сторінку з псевдонімом (alias) cabinet та задайте їй вміст ресурсу [[CombaHelper? &action=`cabinet`]]. Це буде сторінка з історіює замовленнь покупця.
   

Інше:
Options::detectLanguage() визначає поточну мову та керує завантаженням перекладів.
до прикладу файл з українською мовою /combacart/assets/lang/uk.php


## Обробка замовлень ##

Після того як кліент сформував Замовлення (Кошик з товарами відправлено до обробки менеджерами Макретплейса), замовлення можна подивитись на сторінці керування.
Доступ до сторінки має будь-якій користувач з ролю 'manager' що пройшов авторизацію в адмінці EVO.
Відкрити сторінку керування замовленнями можна за посиланням http(s)://назва_сайту/comba
На сторінці керування можна:
- передивлятись перелік замовленнь за будь-який час
- вести пошук замовлень за номером, Замовником та його електроною поштою
- редагувати замовлення
- друкувати замовлення
- надсилати електроній листи, смс повідомлення та формувати тексти для подальшого пересилання в стороніх месенджерах

Присутня можливість зміни мови та теми інтерфейсу.


## Залежності та вимоги до налаштувань #

0. якщо отримуєте помилку Class 'IntlDateFormatter' not found
встановіть та активуйте extension php_intl

1. twig (необхідно) *наявно в composer.json
https://twig.symfony.com/
CombaCart використовує twig для шаблонів (після обробки даних парсером Modx/Evo)

2. boostrap,bootstrap-icon (необхідно,опціонально) *наявно в composer.json
верстка СombaCart використовує bootstrap 5.  
https://getbootstrap.com/ 

якщо маєте наявну копію bootstrap змінить шляхи до вашого bootstrap в файлі snippetGoodsFooter.php

3. bootbox.js (опціонально) *наявно в пакеті інсталяції
для роботи з діалоговими формами bootstrap
https://bootboxjs.com/

4. phpthumb (необхідно) *встановлюється з extras
якщо користуєтесь іншим обробником зображень ніж phpthumb змінить файл snippetGetImage.php під свої потреби.

5. claviska\SimpleImage (опціонально) *наявно в composer.json
composer require claviska/simpleimage

6. cropper.js (опціонально) *наявно в пакеті інсталяції
опис по налаштуванню cropper https://github.com/extras-evolution/multiTV

7. venobox (опціонально) *наявно в пакеті інсталяції
для роботи з діалоговими формами зображень
https://veno.es/venobox/

8. multiTV (опціонально) *встановлюється з extras
https://github.com/extras-evolution/multiTV
використовується для списків зображень в TV goods_images. замість списків зображень можете використовувати TV goods_images як "строка" для единого зображення.
використовується для списків підвидів товару в TV goods_goods. якщо вам це не потрібно - не заповнюйте.

9. reCaptcha (опціонально)
заповніть параметри assets/custom/Config/config_custom.php якщо бажаете використовати капчу при перевірці оформлення замовлення
'reCaptcha' => array(
    'key' => '',
    'secret' => '',
    'url' => 'https://www.google.com/recaptcha/api/siteverify',
    )
