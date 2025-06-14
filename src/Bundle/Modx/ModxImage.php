<?php

namespace Comba\Bundle\Modx;


use Comba\Core\Entity;
use Comba\Core\Logs;
use DocumentParser;

class ModxImage
{
    private DocumentParser $_modx;

    private string $path;
    private string $defaultImagesFolder = 'assets/images';
    private string $defaulCacheImagesFolder = 'assets/cache/images';
    private int $permissions;
    private array $presets;

    // Для обробника зображень.
    // налаштування пропорцій за замовчуванням
    // де img16x9 це назва пропорції що має збігатися з назвами пропорцій з файлу assets/tvs/multitv/configs/goods_images
    private array $ratio_default = [
        'img16x9' => 'far=C',
        'img4x3' => 'zc=C',
        'img1x1' => 'zc=C',
        'img2x3' => 'zc=C',
    ];

    public function __construct(?DocumentParser $modx = null)
    {
        $this->setModx($modx);
        $this->path = $this->getModx()->getConfig('base_path');
        $this->presets = Entity::getData('Imagepresets');
        $this->permissions = octdec($this->getModx()->getConfig('new_folder_permissions') ?: 0755);
    }

    public function getModx(): DocumentParser
    {
        return $this->_modx;
    }

    public function setModx(DocumentParser $modx): ModxImage
    {
        $this->_modx = $modx;
        return $this;
    }

    /**
     * Аргументи:
     * src шлях до зображення
     * id ідентифікатор сторінки
     *
     * preset назва пресету з предналаштованими параметрами обробника
     * phpthumb рядок налаштувань обробника в форматі phpThumb (якщо не хочемо використовувати preset)
     * imgratio рядок налаштувань пропорцій в форматі phpThumb
     * n номер зображення в списку (якщо використовуються списки зображень multiTV)
     * flags може містити 'src,webp,forced,lazy'
     * де:
     *      src - повернути ім'я файлу зображення без обробки
     *      webp - перетворити зображення в формат в webp (ігнорує завданий формат в preset)
     *      force - перезаписати вихідне зображення (видалити та створити нове) в кеші
     *      lazy - додає до результату рядок ' src="data:image/png......"  з заглушкою в форматі base64
     **/
    public function get(array $args): ?string
    {
        $n = $args['n'] ?? null;
        $id = $args['id'] ?? null;
        $src = $args['src'] ?? null;
        $flags = $args['flags'] ?? null;
        $preset = $args['preset'] ?? null;
        $imgratio = $args['imgratio'] ?? null;
        $phpthumb = $args['phpthumb'] ?? null;

        $ratio_sfx = '';
        $options = '&options=`zc=C`';
        $preset = $ratio = $preset ?? $phpthumb ?? 'image-max';

        // по назві пресета дістаємо налаштування
        if ($_item = $this->presets[$preset] ?? null) {
            $ratio = $_item['ratio'];
            $options = $_item['value'];
            $ratio_sfx = ",ratio=" . $_item['name']; // буде частиною шляху до файла
        }

        // якщо передано пропорції
        $imgratio = isset($imgratio) ? (json_decode($imgratio, true)[$ratio] ?? null) : null;

        // порядковий номер зображення
        $n = (isset($n) && is_numeric($n) && $n > 0) ? (int)$n - 1 : 0;

        if (empty($src) && !empty($id) && is_numeric($id)) {

            $modxobject = $this->getModx()->getDocumentObject('id', $id, 'all');
            $imagesTV = Entity::get('TV_GOODS_IMAGES');

            $_images = $modxobject[$imagesTV][1];
            if (!empty($_images) && strlen($_images) > 4) {
                $src = $_images; // без multiTV це буде шліх до зображення
                $mtv = json_decode($_images, true);
                if (!empty($mtv) && is_array($mtv)) {
                    $src = $mtv['fieldValue'][$n]['image'] ?? null;
                    $imgratio = !empty($mtv['fieldValue'][$n][$ratio]) ? $mtv['fieldValue'][$n][$ratio] : $imgratio ?? '';

                    // перетворюємо multiTV дані у формат phpthumb
                    $imgratio = !empty($imgratio) ? str_replace(
                        ['x:', 'y:', 'width:', 'height:', ':', ','],
                        ['sx=', 'sy=', 'sw=', 'sh=', '=', '&'],
                        $imgratio
                    ) : '';
                }
            }
        }

        if (!empty($imgratio)) {
            $this->ratio_default[$ratio] = $imgratio;
        }

        if (!empty($this->ratio_default)) {
            foreach ($this->ratio_default as $key => $value) {
                $options = str_replace($key, $value, $options);
            }
        }
        $options .= $ratio_sfx;

        if (isset($phpthumb)) {
            foreach (explode("&", $phpthumb) as $v) {
                $options .= ',' . $v;
            }
        }

        if (strpos($flags, 'webp') !== false) {
            $options .= ',webp';
        }
        if (strpos($flags, 'forced') !== false) {
            $options .= ',forced';
        }

        $text = null;
        if (preg_match('/\bwatermark\b/', $options)) {
            $text = Entity::get('WATERMARK_TEXT') ?? null;
            if (!$text) {
                $site = filter_var(Entity::get('SERVER_NAME'), FILTER_SANITIZE_URL);
                $text = [
                    $site => [
                        'fontFile' => dirname(__DIR__, 3) . '/assets/font/ApeMount-WyPM9.ttf',
                        'size' => '42',
                        'color' => ['rgb' => '#FF0000', 'alpha' => 20]
                    ]
                ];
            }
        }

        // не використовувати заглушку?
        $noph = strpos($flags, 'noph') !== false;
        if (empty($src) && $noph) {
            return $src;
        }

        // Якщо вхідний файл "недоступний", або він не в теці /images
        // то замінємо його на заглушку
        $path = $this->path;
        if (!file_exists($path . $src) || (strpos(realpath($path . $src), realpath($path . 'assets/images')) !== 0)) {
            $noImageFile = $this->getRandomNoImageFile($noImageFilePath ?? null);
            $path_parts = pathinfo($noImageFile);
            $outputFilename = $this->defaultImagesFolder . '/' . $path_parts['basename'];
            $noImageFile = $this->renderImageInterventionImage($noImageFile, null, null, $outputFilename);
            $src = $noImageFile;
        }

        // Якщо операція вимагає лише src, повертаємо його без створення вихідного файлу
        if (strpos($flags, 'src') !== false) {
            return $src;
        }

        $out = !empty($src) ? $this->renderImageInterventionImage($src, $options, $text) : '';


        $_ = strtr($options, [',' => '&', '_' => '=', '{' => '[', '}' => ']']);
        parse_str($_, $_s);

        if (strpos($flags, 'dw') !== false) {
            if (!empty($_s['w']) && is_numeric($_s['w'])) {
                $out .= ' '. $_s["w"] . 'w';
            }
        }

        if (strpos($flags, 'lazy') !== false) {
            $out .= '" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=';
        }

        if (preg_match('/sow|soh|sof/', $flags)) {
            $_ = strtr($options, [',' => '&', '_' => '=', '{' => '[', '}' => ']']);
            parse_str($_, $_s);

            $hasW = preg_match('/sow|sof/', $flags);
            $hasH = preg_match('/soh|sof/', $flags);

            if ($hasW && !empty($_s['w']) && is_numeric($_s['w'])) {
                $out .= '" width="' . $_s['w'];
            }

            if ($hasH && !empty($_s['h']) && is_numeric($_s['h'])) {
                $out .= '" height="' . $_s['h'];
            }
        }

        return $out;
    }

    /** Повертає шлях до одного з файлів заглушки
     * @param string|null $customPath
     * @param int|null $pos
     * @return string|null
     */
    private function getRandomNoImageFile(string $customPath = null, ?int $pos = null): ?string
    {
        $path = $this->getModx()->getConfig('base_path');

        $dir = dirname(__DIR__, 3) . '/assets/img/placeholders';
        $files = glob($dir . '/noimage*.{jpg,png}', GLOB_BRACE);
        if (null) {
            $file = !empty($files) && $files[null] ? $files[null] : $files[0];
        } else {
            $file = !empty($files) ? $files[array_rand($files)] : null;
        }

        $customFiles = glob($customPath . '/noimage*.{jpg,png}', GLOB_BRACE);
        if (null) {
            $customFile = !empty($customFiles) && $customFiles[null] ? $customFiles[null] : $customFiles[0];
        } else {
            $customFile = !empty($customFiles) ? $customFiles[array_rand($customFiles)] : null;
        }

        $file = !empty($customFile) ? $customFile : $file;
        return $file ? str_replace($path, '', $file) : null;
    }

    /**
     * @param string $input
     * @param string|null $options в форматі phpThumb
     * @param array|null $text налаштування текстового ватермарку
     * @param string|null $overrideOutput шлях та назва вихідного файлу (для заглушки)
     * @return string
     */
    public function renderImageInterventionImage(string $input, ?string $options, ?array $text = null, ?string $overrideOutput = null): string
    {

        if (empty($input) || strtolower(substr($input, -4)) == '.svg') {
            return $input;
        }
        if (!file_exists($this->sanitizePath($this->path . $input))) {
            return $input;
        }

        // Парсимо параметри
        $options = strtr($options, [',' => '&', '_' => '=', '{' => '[', '}' => ']']);
        parse_str($options, $params);

        // Формуемо формат вихідного файлу
        $path_parts = pathinfo($input);
        $ext = strtolower($path_parts['extension']);
        $outputFormat = $params['f'] ?? (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $ext : 'jpg');

        $outputFormat = isset($params['webp']) ? 'webp' : $outputFormat;

        // Додаємо розміри до шляху кешу (якщо вказані)
        $pathopt = '';
        if (!empty($params['w']) || !empty($params['h'])) {
            // Автоматичний розрахунок висоти, якщо вказано ratio
            if (!empty($params['ratio']) && !empty($params['w'])) {
                if (is_numeric($params['ratio']) && is_numeric($params['w'])) {
                    $params['h'] = intval($params['w'] / $params['ratio']);
                }
            }

            $pathopt .= ($params['w'] ?? '') . 'x' . ($params['h'] ?? '');
            $pathopt .= !empty($params['ratio']) && !is_numeric($params['ratio']) ? '_' . $params['ratio'] : '';
        }

        // Формуємо ім'я вихідного файлу
        $outputPathFilename = $this->defaulCacheImagesFolder . '/' . $pathopt . '/' . str_replace([$this->defaultImagesFolder, '/' . $this->defaultImagesFolder], ['', ''], $path_parts['dirname']) . '/' . $path_parts['filename'] . '.' . $outputFormat;
        $outputPathFilename = $this->sanitizePath($overrideOutput ?? $outputPathFilename);
        $outputFullPathFilename = $this->sanitizePath($this->path . $outputPathFilename);

        // Якщо зображення ще не існує або потрібно оновити
        if (!file_exists($outputFullPathFilename) || isset($params['forced'])) {

            // Створюємо теку для кешу
            $_ = dirname($outputFullPathFilename);
            if (!is_dir($_)) {
                mkdir($_, $this->permissions, true);
                chmod($_, $this->permissions);
            }

            try {
                $manager = new \Intervention\Image\ImageManager(); // gd або imagick
                $image = $manager->make($this->sanitizePath($this->path . $input));

                if (!empty($params['sx']) && !empty($params['sy']) && !empty($params['sw']) && !empty($params['sh'])) {
                    $image->crop(
                        (int)$params['sw'],
                        (int)$params['sh'],
                        (int)$params['sx'],
                        (int)$params['sy']
                    );
                }

                // Обробка ресайзу
                if (!empty($params['w']) && !empty($params['h'])) {
                    $image->fit(
                        (int)$params['w'],
                        (int)$params['h'],
                        function ($constraint) {
                            $constraint->aspectRatio();
                            //$constraint->upsize(); // Заборонити збільшення маленьких зображень
                        },
                        $params['position'] ?? 'center'
                    );
                } elseif (!empty($params['w'])) {
                    $image->resize((int)$params['w'], null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                } elseif (!empty($params['h'])) {
                    $image->resize(null, (int)$params['h'], function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                // Додаткові параметри якості
                $quality = isset($params['q']) ? (int)$params['q'] : 90;
                $quality = max(0, min(100, $quality));

                // Формат зображення
                $extension = strtolower(pathinfo($outputPathFilename, PATHINFO_EXTENSION));
                $format = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $extension : 'jpg';

                // Додаємо текст
                if (!empty($text)) {
                    $this->addInterventionCenteredText($image, $text);
                }

                $image->save($outputFullPathFilename, $quality, $format);

            } catch (\Exception $e) {
                (new Logs())->log('ERROR', 'Intervention Image Error: ' . $e->getMessage());
                return '';
            }
        }

        // формуємо webp зображення (швидкий варіант)
        // потрібно окремо встановлювати в систему https://developers.google.com/speed/webp
        if (!empty($params['webp']) && $outputFormat !== 'webp') {
            $outputFilenameWebp = $outputPathFilename . '.webp';
            if (!file_exists($this->path . $outputFilenameWebp)) {
                exec('cwebp -q 80 "' . $outputFullPathFilename . '" -o "' . $this->sanitizePath($this->path . $outputFilenameWebp) . '"');
            }
            $outputPathFilename = $outputFilenameWebp;
        }

        return $outputPathFilename;
    }

    private function sanitizePath(string $path): string
    {
        $path = preg_replace('#^(\.\./)+#', '', $path);
        return preg_replace('#/+#', '/', $path);
    }

    public function addInterventionCenteredText(\Intervention\Image\Image $image, array $text_param): bool
    {
        if (empty($text_param)) {
            return false;
        }

        $text = key($text_param);
        $text_param = current($text_param);
        $fontFile = $text_param['fontFile'] ?? null;
        $fontSize = $text_param['size'] ?? 12;
        $minFontSize = 8; // Мінімальний розмір шрифту, менше не будемо пробувати
        $textFits = false;

        if (empty($fontFile)) {
            return false;
        }

        $width = $image->width();
        $height = $image->height();

        // Функція для отримання розміру тексту (костыль, бо getTextSize() немає)
        $getTextSize = function ($text, $fontSize) use ($fontFile) {
            $box = imagettfbbox($fontSize, 0, $fontFile, $text);
            return [
                'width' => abs($box[2] - $box[0]),
                'height' => abs($box[7] - $box[1])
            ];
        };

        // Перевіряємо, чи влізе текст
        while ($fontSize >= $minFontSize) {
            $size = $getTextSize($text, $fontSize);

            if ($size['width'] < $width && $size['height'] < $height) {
                $textFits = true;
                break;
            }

            $fontSize -= 2;
        }

        if (!$textFits) {
            return false;
        }

        // Координати для центрування
        $x = max(0, ($width - $size['width']) / 2);
        $y = max(0, ($height - $size['height']) / 2 + $size['height']); // +height для корекції baseline
        $color = $this->hexToRgba($text_param['color']['rgb'] ?: '#FFFFFF', $text_param['color']['alpha'] ?? 20);

        $image->text($text, $x, $y, function (\Intervention\Image\AbstractFont $font) use ($fontFile, $fontSize, $color) {
            $font->file($fontFile);
            $font->size($fontSize);
            $font->color($color);
            $font->align('left'); // Вирівнювання вже враховане в координатах
        });

        return true;
    }

    /** Допоміжна функція для конвертації HEX+Opacity в RGBA
     * @param $hex
     * @param $opacity
     * @return string
     */
    private function hexToRgba($hex, $opacity): string
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $a = str_replace(',', '.', round($opacity / 100, 2));

        return "rgba($r, $g, $b, $a)";
    }

    /** Видалення зображень з кешу
     * @param string $filename
     * @return void
     */
    public function deleteCacheVariants(string $filename): void
    {
        $searchDir = $this->sanitizePath($this->path . $this->defaulCacheImagesFolder);

        $pos = strpos($filename, $this->defaultImagesFolder);
        if ($pos === false) {
            (new Logs())->log('DEBUG', "Файл не в теці зображень, скіпаємо " . $filename);
            return;
        }

        $relative = substr($filename, $pos + strlen($this->defaultImagesFolder));
        $pathWithoutExt = preg_replace('/\.\w+$/', '', $relative);

        $dirs = glob($searchDir . '/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $pattern = $this->sanitizePath($dir . '/' . $pathWithoutExt) . '.*';

            foreach (glob($pattern) as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
