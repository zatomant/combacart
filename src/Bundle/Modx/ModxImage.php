<?php

namespace Comba\Bundle\Modx;

use claviska\SimpleImage;
use Comba\Core\Entity;
use Comba\Core\Logs;
use DocumentParser;

class ModxImage
{
    private DocumentParser $_modx;

    private array $ratio_default = array(
        'img16x9' => 'far=C',
        'img4x3' => 'zc=C',
        'img1x1' => 'zc=C',
        'img2x3' => 'zc=C',
    );

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
        $options = '&options=`zc=C`'; // far=C,bg=ffffff
        $preset = $ratio = $preset ?? 'image-max';

        if ($_item = $this->presets[$preset] ?? null) {
            $ratio = $_item['ratio'];
            $options = $_item['value'];
            $ratio_sfx = ",ratio=" . $_item['name'];
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
                        'rowTpl' => '@CODE:((image1));'
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
                foreach ($_images['fieldValue'] as $item) {

                    if (isset($item['image'])) {
                        $imgratio = $item[$ratio] ?? null;

                        // convert multitv image`s data for use in phpthumb class
                        $imgratio = str_replace(array(':', 'x', 'y', 'width', 'height', ','), array('=', 'sx', 'sy', 'sw', 'sh', '&'), $imgratio);

                        $this->ratio_default[$ratio] = $imgratio;
                        break; // get only one image for sample
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

        $site = filter_var(Entity::get('SERVER_NAME'), FILTER_SANITIZE_URL);
        $options = str_replace('watermark', 'wmt|' . $site . '|20|*|f0f0f0|ApeMount-WyPM9.ttf|70', $options);

        $text = [
            $site => [
                'fontFile' => $this->getModx()->getConfig('base_path') . 'assets/plugins/combacart/assets/font/ApeMount-WyPM9.ttf',
                'size' => '42',
                'color' => array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 0.2)
            ]
        ];

        if (!empty($force)) {
            $options .= ',force=1';
        }

        $out = $this->renderImage(
            $src,
            $options,
            $text,
            'assets/plugins/combacart/assets/img/noimage.png'
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

    public function renderImage(string $input, string $options, array $text, string $noImage): string
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

                // ватермарк
                if (!empty($text)) {
                    if (file_exists($outputFilename)) {

                        $_text = key($text);
                        $_text_param = array_values($text);

                        if (!empty($_text) && !empty($_text_param) && file_exists($_text_param[0]['fontFile'])) {
                            if (class_exists('SimpleImage')) {
                                $image = new SimpleImage();
                                $image
                                    ->fromFile($outputFilename)
                                    ->text($_text, $_text_param[0])
                                    ->toFile($outputFilename);
                            }
                        }
                    }
                }
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
