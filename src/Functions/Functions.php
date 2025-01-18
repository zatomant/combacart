<?php

namespace Comba\Functions;

/**
 * filtered by preg_replace a-z0-9_
 *
 * @param null|string $value
 * @return string|null
 */
function sanitizeID(?string $value): ?string
{
    return !empty($value) ? preg_replace("/[^a-z0-9_-]/i", '', $value) : null;
}

function sanitize(string $text): string
{
    $text = str_replace("<", "&lt;", $text);
    $text = str_replace(">", "&gt;", $text);
    $text = str_replace("\"", "&quot;", $text);
    $text = str_replace("'", "&#039;", $text);
    $text = str_replace("\'", "&#039;", $text);
    $text = str_replace("&#9;", "", $text);
    $text = str_replace("\t", "", $text);
    $text = str_replace(":", "", $text);
    return addslashes($text);
}

function safeHTML(?string $value): string
{
    return htmlentities($value, ENT_QUOTES, 'utf-8');
}

/** a[k]
 * @param array $array
 * @param string $key
 * @return mixed|null
 */
function array_search_by_key(array $array, string $key)
{
    return $array[$key] ?? null;
}

/**
 * сортує масив за полем
 *
 * @param array $array
 * @param string $on
 * @param int $order
 * @return array
 */
function array_sort(array $array, string $on, int $order = SORT_ASC): array
{
    $new_array = array();
    $sortable_array = array();
    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }
        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
                break;
            case SORT_DESC:
                arsort($sortable_array);
                break;
        }
        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }
    return $new_array;
}


/**
 * повертає масив з requestKeys враховуючі $ignoreKeys
 *
 * @param array $array
 * @param array|null $requestKeys
 * @param array|null $ignoreKeys
 * @return array
 */
function filterArrayRecursive(array $array, ?array $requestKeys = null, ?array $ignoreKeys = null): array
{
    $filteredArray = [];

    foreach ($array as $key => $value) {
        // Пропускаємо ключ, якщо він є в списку ключів, які потрібно ігнорувати
        if ($ignoreKeys !== null && in_array($key, $ignoreKeys)) {
            continue;
        }

        // Якщо $requestKeys є null, включаємо всі ключі, крім тих, що в $ignoreKeys
        if (is_null($requestKeys) || in_array($key, $requestKeys)) {
            if (is_array($value)) {
                $filteredArray[$key] = filterArrayRecursive($value, $requestKeys, $ignoreKeys);
            } else {
                $filteredArray[$key] = $value;
            }
        } elseif (is_array($value)) {
            // Якщо ключ не в запиті, але значення - це масив, перевіряємо його рекурсивно
            $nestedFiltered = filterArrayRecursive($value, $requestKeys, $ignoreKeys);
            if (!empty($nestedFiltered)) {
                $filteredArray[$key] = $nestedFiltered;
            }
        }
    }

    return $filteredArray;
}

function recursive_array_search_key_value(
    array  $array,
    string $searchValue,
    string $searchKey = 'name',
    bool   $returnArray = false,
    string $returnKeyValue = null
)
{
    foreach ($array as $key => $value) {
        if (isset($value[$searchKey]) && $value[$searchKey] == $searchValue) {
            // Повертаємо масив, якщо $returnArray = true
            if ($returnArray) {
                return $returnKeyValue ? ($value[$returnKeyValue] ?? false) : $value;
            }
            // Повертаємо значення за ключем $returnKeyValue або ключ масиву
            return $returnKeyValue ? ($value[$returnKeyValue] ?? false) : $key;
        }

        // Рекурсивний пошук, якщо $value — вкладений масив
        if (is_array($value)) {
            $result = recursive_array_search_key_value($value, $searchValue, $searchKey, $returnArray, $returnKeyValue);
            if ($result !== false) {
                return $result;
            }
        }
    }

    return false;
}






