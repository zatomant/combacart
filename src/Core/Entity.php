<?php

namespace Comba\Core;

class Entity
{

    private static array $data = [
        // Основні параметри
        'NAME' => 'CombaCart',
        'VERSION' => '2.6',
        'FILE_VER' => '43',
        // Шляхи
        'PATH_SRC' => '/src',
        'PATH_ASSETS' => '/assets',
        'PATH_BUNDLE' => '/src/Bundle',
        'PATH_CUSTOM' => '/assets/custom',
        'PATH_THEMES' => '/src/Themes',
        'PATH_TEMPLATES' => '/src/Themes/templates',
    ];

    private static array $protectedKey = [
        'MYFILE_SECRET' => [\Comba\Core\MyFile::class,],
        'USER_SALT' => [\Comba\Bundle\Modx\ModxUser::class]
    ];

    private static bool $_initialized = false;

    /**
     * Отримання даних аутентифікації
     *
     * @param string $provider
     * @param string $seller UID
     * @return array
     */
    public static function get3thAuth(string $provider, string $seller): array
    {
        if (empty(self::$data)) {
            self::loadData();
        }

        // по Продавцю
        $sellerData = self::$data['Provider'][$provider][$seller] ?? null;

        if ($sellerData !== null) {
            // Перевірка на вимкнені дані
            if (isset($sellerData['enabled']) && $sellerData['enabled'] === false) {
                return self::get3thAuth($provider, 'marketplace');
            }
            return $sellerData;
        }

        // Відсутні дані по Продавцю - повертаємо дані за замовчуванням (що діють для всього маркетплейса)
        // а якщо Продавець був 'marketplace', повертаємо порожній масив
        return $seller !== 'marketplace' ? self::get3thAuth($provider, 'marketplace') : [];
    }

    /**
     * Завантаження налоаштувань з файлів
     * @return void
     */
    private static function loadData(): void
    {
        self::$data['VERSION'] = self::$data['VERSION'] . '.' . self::$data['FILE_VER'];
        self::$data['PATH_ROOT'] = dirname(__FILE__, 3);
        self::$data['SERVER_HOST'] = self::getServerHost();
        self::$data['SERVER_NAME'] = self::getServerName();

        $_configFiles = array_merge(
            glob(dirname(__DIR__) . '/Config/[^_]*.php') ?: [],
            glob(dirname(__DIR__, 2) . self::$data['PATH_CUSTOM'] . '/Config/[^_]*.php') ?: []
        );

        foreach ($_configFiles as $file) {
            if (is_file($file)) {
                try {
                    $_custom = (static function (string $path): array {
                        $data = require $path;

                        if (!is_array($data)) {
                            throw new \RuntimeException("Файл конфігурації '$path' повинен повертати масив.");
                        }

                        return $data;
                    })($file);

                    self::$data = self::update(self::$data, $_custom);

                } catch (\Throwable $e) {
                    error_log("[ConfigLoadError] {$e->getMessage()} in {$file}");
                    continue;
                }
            }
        }

        self::applyPlaceholders(self::$data);

        self::$_initialized = true;
    }

    private static function getServerHost(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    private static function getServerName(): string
    {
        return $_SERVER['SERVER_NAME'] ?? getenv('SERVER_NAME') ?? 'localhost';
    }

    /**
     * Оновлення, видалення значень конфігурації
     */
    public static function update(array $data, array $instructions): array
    {
        if (self::isAssoc($instructions)) {
            self::$data = array_merge($data, $instructions);
            return self::$data;
        }

        $_Deleted = false;
        foreach ($instructions as $instruction) {

            // редагування та додавання за умовою пошуку
            if (isset($instruction['_match'])) {
                $data = self::apply($data, $instruction);
            } else {

                // Обробка _add у корінь або до наявних ключів-масивів
                if (isset($instruction['_add'])) {
                    foreach ($instruction['_add'] as $addKey => $addValue) {

                        if (isset($data[$addKey]) && is_array($data[$addKey])) {
                            // Додаємо до існуючого масиву
                            if (self::isAssoc($data[$addKey])) {
                                // асоціативно
                                $data[$addKey] = array_merge($data[$addKey], $addValue);
                            } else {
                                // як список
                                if (self::isAssoc($addValue)) {
                                    $data[$addKey][] = $addValue;
                                } else {
                                    foreach ($addValue as $v) {
                                        $data[$addKey][] = $v;
                                    }
                                }
                            }
                        } else {
                            // Просто додаємо новий ключ
                            $data[$addKey] = $addValue;
                        }
                    }
                } elseif (isset($instruction['_delete'])) {

                    foreach ($instruction['_delete'] as $deleteKey => $deleteItems) {

                        if (isset($data[$deleteKey]) && is_array($data[$deleteKey])) {
                            foreach ($deleteItems as $deleteItem) {
                                foreach ($data[$deleteKey] as $index => $item) {
                                    // Якщо ключі збігаються, видаляємо
                                    foreach ($deleteItem as $deleteSubKey => $deleteSubValue) {

                                        if (isset($item[$deleteSubKey]) && $item[$deleteSubKey] == $deleteSubValue) {
                                            // Якщо умови збігаються, видаляємо елемент
                                            $_Deleted = true;
                                            unset($data[$deleteKey][$index]);
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

            }
        }

        // Перезаписуємо індекси масиву після видалення
        if ($_Deleted) {
            foreach ($data as $key => $value) {
                // Якщо елемент є масивом і він асоціативний
                if (is_array($value) && self::isAssoc($value)) {
                    // Не перенумеровуємо індекси для асоціативних масивів
                    continue;
                }

                // Якщо це числовий масив, перенумеровуємо індекси
                if (is_array($value)) {
                    $data[$key] = array_values($value);
                }
            }
        }

        return $data;
    }

    /**
     * хелпер isAssoc
     */
    private static function isAssoc(array $arr): bool
    {
        // Якщо масив порожній, повертаємо false (не асоціативний)
        if ([] === $arr) {
            return false;
        }

        // Перевірка, чи є ключі нечисловими (асоціативний масив)
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Обробка макросів згідно пошуку з matches
     */
    private static function apply(array $data, array $instruction): array
    {
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                if (self::matches($item, $instruction['_match'] ?? [])) {
                    $item = self::applyActions($item, $instruction);
                }

                // Рекурсивний обхід
                $data[$key] = self::apply($item, $instruction);
            }
        }
        return $data;
    }

    /**
     * Обробка макроса пошуку matches
     */
    private static function matches(array $item, array $conditions): bool
    {
        foreach ($conditions as $key => $value) {
            if (!array_key_exists($key, $item)) {
                return false;
            }

            if (is_array($value)) {
                if (!is_array($item[$key]) || !self::matches($item[$key], $value)) {
                    return false;
                }
            } elseif ($item[$key] != $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Обробка макросів update,add, delete
     */
    private static function applyActions(array $item, array $instruction): array
    {
        if (isset($instruction['_update'])) {
            foreach ($instruction['_update'] as $updateKey => $updateValue) {
                if (is_array($updateValue) && isset($item[$updateKey]) && is_array($item[$updateKey])) {
                    // Якщо елемент є списком з вкладеним _match
                    if (isset($updateValue[0]) && isset($updateValue[0]['_match'])) {
                        foreach ($item[$updateKey] as $i => $subItem) {
                            foreach ($updateValue as $subUpdate) {
                                if (self::matches($subItem, $subUpdate['_match'])) {
                                    $item[$updateKey][$i] = self::recursiveMerge($subItem, $subUpdate['_update']);
                                }
                            }
                        }
                    } else {
                        $item[$updateKey] = self::recursiveMerge($item[$updateKey], $updateValue);
                    }
                } else {
                    $item[$updateKey] = $updateValue;
                }
            }
        }

        if (isset($instruction['_add'])) {

            foreach ($instruction['_add'] as $addKey => $addValue) {
                if (!isset($item[$addKey]) || !is_array($item[$addKey])) {
                    $item[$addKey] = [];
                }

                if (self::isAssoc($addValue)) {
                    $item[$addKey][] = $addValue;
                } else {
                    foreach ($addValue as $v) {
                        $item[$addKey][] = $v;
                    }
                }
            }
        }

        if (isset($instruction['_delete'])) {
            foreach ($instruction['_delete'] as $deleteKey => $deleteCond) {
                if (isset($item[$deleteKey]) && is_array($item[$deleteKey])) {
                    $item[$deleteKey] = array_values(array_filter(
                        $item[$deleteKey],
                        fn($subItem) => !self::matches($subItem, $deleteCond)
                    ));
                }
            }
        }

        return $item;
    }

    private static function recursiveMerge(array $base, array $updates): array
    {
        foreach ($updates as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = self::recursiveMerge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    /**
     * Обробка плейсхолдерів
     *
     * @param array $data
     * @param array $source
     * @return void
     */
    public static function applyPlaceholders(array &$data, array $source = null): void
    {
        $source = $source ?? $data;
        array_walk_recursive($data, function (&$value) use ($source) {
            if (is_string($value)) {
                $value = self::replacePlaceholders($source, $value);
            }
        });
    }

    private static function replacePlaceholders(array $data, string $text): string
    {
        $limit = 10; // захист від нескінченного циклу
        while ($limit-- > 0 && preg_match('/@_get\((.*?)\)/', $text)) {

            $text = preg_replace_callback('/@_get\((.*?)\)/', function ($matches) use ($data) {
                $path = trim($matches[1]);
                $val = self::getValueByPath($data, $path);
                if (is_string($val)) {
                    return self::replacePlaceholders($data, $val); // рекурсивна підстановка
                }
                return is_scalar($val) ? $val : '';
            }, $text);

        }
        return $text;
    }

    private static function getValueByPath(array $array, string $path)
    {
        $path = self::isProtectedKey($path) ? self::getProtected($path) : $path;

        $parts = preg_split('/\./', $path);
        foreach ($parts as $part) {
            if (is_array($array) && array_key_exists($part, $array)) {
                $array = $array[$part];
            } elseif (is_array($array) && is_numeric($part) && array_key_exists((int)$part, $array)) {
                $array = $array[(int)$part];
            } else {
                return null;
            }
        }
        return is_scalar($array) ? $array : null;
    }

    /**
     * Отримання масиву даних
     *
     * @param string $entity
     * @return array
     */
    public static function getData(string $entity): array
    {
        if (!self::$_initialized) {
            self::loadData();
        }

        return !empty(self::$data[$entity]) ? self::$data[$entity] : [];
    }

    /**
     * Отримання значення захищених ключів конфігурації
     * @return mixed|null
     */
    public static function getProtected(string $key)
    {
        if (!self::isProtectedKey($key)) {
            return self::get($key);
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1]['class'] ?? '';

        $allowed = self::$protectedKey[$key];

        if (!in_array($caller, $allowed, true)) {
            $message = "Доступ до ключа '$key' з класу '$caller' заборонено";
            error_log($message);
            return null;
        }

        return self::$data[$key] ?? null;
    }

    private static function isProtectedKey(string $key): bool
    {
        return in_array($key, array_keys(self::$protectedKey), true);
    }

    /**
     * Отримання значення конфігурації
     * @return mixed|null
     */
    public static function get(string $key)
    {
        if (!self::$_initialized) {
            self::loadData();
        }

        if (self::isProtectedKey($key)) {
            $message = "Спроба доступу до захищеного ключа: '$key'";
            error_log($message);
            return null;
        }

        return self::$data[$key] ?? null;
    }

}
