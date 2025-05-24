<?php

namespace Comba\Bundle\Modx;


use Comba\Core\Entity;
use Comba\Core\Logs;
use DocumentParser;

class ModxImage
{
    private DocumentParser $_modx;

    // налаштування пропорцій (для обробника зображень) за замовчуванням
    // де img16x9 це назва пропорції що має збігатися з назвами пропорцій з файлу assets/tvs/multitv/configs/goods_images
    private array $ratio_default = [
        'img16x9' => 'far=C',
        'img4x3' => 'zc=C',
        'img1x1' => 'zc=C',
        'img2x3' => 'zc=C',
    ];

    private array $presets;

    public function __construct(?DocumentParser $modx = null)
    {
        $this->presets = Entity::getData('Imagepresets');
        $this->setModx($modx);
    }

    /**
     * getImage без аргументів повертає шлях до кешованого файлу
     * аргументи:
     * &oper=`src`- повернути оригінальне ім'я файлу зображення
     * &webp=`1` використовувати webp для зображень
     * &filemtime=`1` перевіряти файловий час у файлі md5-кешу
     * &force=`1` перезаписати вихідне зображення (видалити та створити нове) в кеші
     **/
    public function getImage(array $args): string
    {
        extract($args);

        $oper = $oper ?? null;

        $ratio_sfx = '';
        $options = '&options=`zc=C`'; // far=C
        $preset = $ratio = $preset ?? 'image-max';

        // по назві пресета дістаємо налаштування
        if ($_item = $this->presets[$preset] ?? null) {
            $ratio = $_item['ratio'];
            $options = $_item['value'];
            $ratio_sfx = ",ratio=" . $_item['name']; // буде частиною шляху до файла
        }

        $src = $src ?? '';
        $id = empty($src) ? ($id ?? $this->getModx()->documentObject['id']) : ($id ?? null);

        if (empty($src)) {

            // get original filename
            if (!empty($id) && is_numeric($id)) {

                $imgs = $this->getModx()->runSnippet('multiTV', [
                        'docid' => $id,
                        'tvName' => Entity::get('TV_GOODS_IMAGES'),
                        'tplConfig' => '',
                        'outerTpl' => '@CODE:((wrapper))',
                        'rowTpl' => '@CODE:((image));'
                    ]
                );

                if (empty($imgs)) {
                    // якщо немає multiTV
                    $modxobject = $this->getModx()->getDocumentObject('id', $id, 'all');
                    if (!empty($modxobject[Entity::get('TV_GOODS_IMAGES')][1])) {
                        $imgs = $modxobject[Entity::get('TV_GOODS_IMAGES')][1];
                    }
                }

                $imgs = explode(';', $imgs);
                $src = $imgs[0] ?? null;
            }

            if (strpos($oper, 'src') !== false) {
                return $src;
            }
        }

        if (!empty($id)) {
            // get ratio
            $modxobject = $this->getModx()->getDocumentObject('id', $id, true);
            $_images = json_decode($modxobject[Entity::get('TV_GOODS_IMAGES')][1], true);

            if (!empty($_images)) {
                // multiTV
                foreach ($_images['fieldValue'] as $item) {

                    if (isset($item['image'])) {
                        $imgratio = $item[$ratio] ?? null;

                        // convert multitv image`s data to phpthumb`s syntax
                        $imgratio = str_replace(
                            [':', 'x', 'y', 'width', 'height', ','],
                            ['=', 'sx', 'sy', 'sw', 'sh', '&'],
                            $imgratio);

                        $this->ratio_default[$ratio] = $imgratio;
                        break;
                    }
                }
            }
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

        if (isset($webp)) {
            $options .= ',webp=1';
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

        if (!empty($force)) {
            $options .= ',force=1';
        }

        $out = $this->renderImageInterventionImage(
            $src,
            $options,
            $text,
            $this->getRandomNoImageFile($noImageFilePath ?? null)
        );

        if (strpos($oper, 'lazy') !== false) {
            $out .= '" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=';
        }

        return $out;
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

    public function renderImageInterventionImage(string $input, string $options, ?array $text, string $noImage): string
    {

        // SVG не обробляємо
        if (!empty($input) && strtolower(substr($input, -4)) == '.svg') {
            return $input;
        }

        // Налаштування прав доступу
        $newFolderAccessMode = $this->getModx()->getConfig('new_folder_permissions');
        $newFolderAccessMode = empty($newFolderAccessMode) ? 0755 : octdec($newFolderAccessMode);

        // Шляхи до кешу
        $defaultCacheFolder = 'assets/cache/';
        $cacheFolder = $cacheFolder ?? $defaultCacheFolder . 'images';

        // Перевірка та створення кеш-папки
        $path = $this->getModx()->getConfig('base_path') . $cacheFolder;
        if (!file_exists($path)) {
            mkdir($path, $newFolderAccessMode, true);
            chmod($path, $newFolderAccessMode);
        }

        // Перевірка вхідного зображення
        if (!empty($input)) {
            $input = rawurldecode($input);
        }

        $inputPath = $this->getModx()->getConfig('base_path') . $input;
        if (empty($input) || !file_exists($inputPath)) {
            $inputPath = $this->getModx()->getConfig('base_path') . $noImage;
        }

        // Парсимо параметри
        $options = strtr($options, [',' => '&', '_' => '=', '{' => '[', '}' => ']']);
        parse_str($options, $params);

        // Визначаємо формат вихідного файлу
        $path_parts = pathinfo($inputPath);
        $ext = strtolower($path_parts['extension']);
        $outputFormat = $params['f'] ?? (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $ext : 'jpg');
        if (!empty($params['webp'])) {
            $outputFormat = 'webp';
        }

        // Додаємо розміри до шляху кешу (якщо вказані)
        $pathopt = '';
        if (!empty($params['w']) || !empty($params['h'])) {
            // Автоматичний розрахунок висоти, якщо вказано ratio
            if (!empty($params['ratio']) && !empty($params['w'])) {
                if (is_numeric($params['ratio']) && is_numeric($params['w'])) {
                    $params['h'] = intval($params['w'] / $params['ratio']);
                }
            }

            $pathopt = '/' . ($params['w'] ?? '') . 'x' . ($params['h'] ?? '');
            $pathopt .= !empty($params['ratio']) && !is_numeric($params['ratio']) ? '_' . $params['ratio'] : '';
        }

        // Створюємо папки для кешу
        $fullCachePath = $path . $pathopt;
        if (!file_exists($fullCachePath)) {
            mkdir($fullCachePath, $newFolderAccessMode, true);
        }

        // Формуємо ім'я вихідного файлу
        $outputFilename = $fullCachePath . '/' . $path_parts['filename'] . '.' . $outputFormat;

        // Якщо зображення ще не існує або потрібно оновити
        if (!file_exists($outputFilename) || !empty($params['force'])) {
            try {
                $manager = new \Intervention\Image\ImageManager(); // gd або imagick

                $image = $manager->make($inputPath);

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
                            $constraint->upsize(); // Заборонити збільшення маленьких зображень
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
                $extension = strtolower(pathinfo($outputFilename, PATHINFO_EXTENSION));
                $format = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $extension : 'jpg';

                // Додаємо текст
                if (!empty($text)) {
                    $this->addInterventionCenteredText($image, $text);
                }

                $image->save($outputFilename, $quality, $format);

            } catch (\Exception $e) {
                (new Logs())->log('ERROR', 'Intervention Image Error: ' . $e->getMessage());
                return $noImage; // Повертаємо зображення-заглушку у разі помилки
            }
        }

        // формуємо webp зображення (швидкий варіант)
        // потрібно окремо встановлювати в систему https://developers.google.com/speed/webp
        if (!empty($params['webp']) && $outputFormat !== 'webp') {
            $outputFilenameWebp = $outputFilename . '.webp';
            if (!file_exists($outputFilenameWebp)) {
                exec('cwebp -q 80 "' . $outputFilename . '" -o "' . $outputFilenameWebp . '"');
            }
            return str_replace($this->getModx()->getConfig('base_path'), '', $outputFilenameWebp);
        }

        return str_replace($this->getModx()->getConfig('base_path'), '', $outputFilename);
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

    private function hexToRgba($hex, $opacity): string
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $a = round($opacity / 100, 2);

        return "rgba($r, $g, $b, $a)";
    }

    // Допоміжна функція для конвертації HEX+Opacity в RGBA

    /** Повертає шлях до файлу заглушки
     * @param string|null $customPath
     * @return mixed|null
     */
    private function getRandomNoImageFile(string $customPath = null)
    {
        $dir = dirname(__DIR__, 3) . '/assets/img/placeholders';
        $files = glob($dir . '/noimage*.{jpg,png}', GLOB_BRACE);
        $file = empty($files) ? null : $files[array_rand($files)];

        $customFiles = glob($customPath . '/noimage*.{jpg,png}', GLOB_BRACE);
        $customFile = empty($customFiles) ? null : $customFiles[array_rand($customFiles)];

        return !empty($customFile) ? $customFile : $file;
    }

    /** DEPRECATED
     * @param string $input
     * @param string $options
     * @param array|null $text
     * @param string $noImage
     * @return string
     */
    public function renderImagePhpThumb(string $input, string $options, ?array $text, string $noImage): string
    {

        if (!empty($input) && strtolower(substr($input, -4)) == '.svg') {
            return $input;
        }

        $newFolderAccessMode = $this->getModx()->getConfig('new_folder_permissions');
        $newFolderAccessMode = empty($new) ? 0755 : octdec($newFolderAccessMode);

        $defaultCacheFolder = 'assets/cache/';
        $cacheFolder = $cacheFolder ?? $defaultCacheFolder . 'images';
        $phpThumbPath = $phpThumbPath ?? 'assets/snippets/phpthumb/';

        /**
         * @see: https://github.com/kalessil/phpinspectionsea/blob/master/docs/probable-bugs.md#mkdir-race-condition
         */
        $path = $this->getModx()->getConfig('base_path') . $cacheFolder;
        if (!file_exists($path) && mkdir($path) && is_dir($path)) {
            chmod($path, $newFolderAccessMode);
        }

        if (!empty($input)) {
            $input = rawurldecode($input);
        }

        if (empty($input) || !file_exists($this->getModx()->getConfig('base_path') . $input)) {
            $input = $noImage ?? $phpThumbPath . 'noimage.jpg';
        }

        /**
         * allow read in phpthumb cache folder
         */
        if (!file_exists($this->getModx()->getConfig('base_path') . $cacheFolder . '/.htaccess') &&
            $cacheFolder !== $defaultCacheFolder &&
            strpos($cacheFolder, $defaultCacheFolder) === 0
        ) {
            file_put_contents($this->getModx()->getConfig('base_path') . $cacheFolder . '/.htaccess', "order deny,allow\nallow from all\n");
        }

        $options = strtr($options, array(',' => '&', '_' => '=', '{' => '[', '}' => ']'));

        $text = $params['text'] ?? ($text ?? null);
        parse_str($options, $params);

        $path_parts = pathinfo($input);
        $tmpImagesFolder = str_replace('assets/images', '', $path_parts['dirname']);
        $tmpImagesFolder = explode('/', $tmpImagesFolder);
        $ext = strtolower($path_parts['extension']);

        if (empty($params['f'])) {
            if (!empty($params['webp'])) {
                $params['f'] = 'webp';
            } else {
                $params['f'] = in_array($ext, array('png', 'gif', 'jpeg')) ? $ext : 'jpg';
            }
        }

        $fmtime = '';
        if (isset($filemtime)) {
            $fmtime = filemtime($this->getModx()->getConfig('base_path') . $input);
        }

        /* mkdir for w&h options */
        $pathopt = '';
        if (!empty($params['w']) || !empty($params['h'])) {

            if (!empty($params['ratio']) && !empty($params['w'])) {
                if (is_numeric($params['ratio']) && is_numeric($params['w'])) {
                    $params['h'] = intval($params['w'] / $params['ratio']);
                }
            }

            $pathopt = '/' . ($params['w'] ?? '') . 'x' . ($params['h'] ?? '');
            $pathopt .= !empty($params['ratio']) && !is_numeric($params['ratio']) ? '_' . $params['ratio'] : '';

            if ($params['w'] >= 500 || $params['h'] >= 500) {
                $options .= 'wmt';
            }
        }

        $path .= $pathopt;
        $cacheFolder .= $pathopt;
        if (!file_exists($path) && mkdir($path) && is_dir($path)) {
            chmod($path, $newFolderAccessMode);
        }
        /* end mkdir */

        foreach ($tmpImagesFolder as $folder) {
            if (!empty($folder)) {
                $cacheFolder .= '/' . $folder;
                $path = $this->getModx()->getConfig('base_path') . $cacheFolder;
                if (!file_exists($path) && mkdir($path) && is_dir($path)) {
                    chmod($path, $newFolderAccessMode);
                }
            }
        }

        $fNamePref = rtrim($cacheFolder, '/') . '/';
        $fName = $path_parts['filename'];
        $fNameSuf = '.' . $params['f'];//$path_parts['extension'];//

        /*
        $fNameSuf = '-' .
            (isset($params['w']) ? $params['w'] : '') . 'x' . (isset($params['h']) ? $params['h'] : '') . '-' .
            substr(md5(serialize($params) . $fmtime), 0, 3) .
            '.' . $params['f'];
        */

        $fNameSuf = str_replace("ad", "at", $fNameSuf);

        $outputFilename = $this->getModx()->getConfig('base_path') . $fNamePref . $fName . $fNameSuf;
        if (!empty($params['force'])) {
            if (file_exists($outputFilename)) {
                unlink($outputFilename);
            }
        }

        if (!file_exists($outputFilename)) {

            if (!class_exists('phpthumb')) {
                require_once $this->getModx()->getConfig('base_path') . $phpThumbPath . '/phpthumb.class.php';
            }

            $phpThumb = new \phpthumb();
            $phpThumb->config_cache_directory = $this->getModx()->getConfig('base_path') . $defaultCacheFolder;
            $phpThumb->config_temp_directory = $defaultCacheFolder;
            $phpThumb->config_document_root = $this->getModx()->getConfig('base_path');
            $phpThumb->setSourceFilename($this->getModx()->getConfig('base_path') . $input);

            foreach ($params as $key => $value) {
                $phpThumb->setParameter($key, $value);
            }
            if ($phpThumb->GenerateThumbnail()) {
                $phpThumb->RenderToFile($outputFilename);
            } else {
                $lg = new Logs();
                $lg->log('ERROR', 'phpThumb->GenerateThumbnail()\n');
                $lg->log('ERROR', json_encode($phpThumb->debugmessages));
            }
        }

        // формуємо webp зображення (швидкий варіант)
        // потрібно окремо встановлювати в систему https://developers.google.com/speed/webp
        if (isset($webp)) {
            if (!file_exists($outputFilename . '.webp')) {
                $outputFilenameWebp = $outputFilename . '.webp';
                exec('cwebp -q 80 "' . $outputFilename . '" -o "' . $outputFilenameWebp . '"');
            }
            $fNameSuf .= '.webp';
        }

        return $fNamePref . $fName . $fNameSuf;
    }

}
