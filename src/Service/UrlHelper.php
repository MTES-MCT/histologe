<?php

namespace App\Service;

class UrlHelper
{
    /**
     * @param array<mixed> $params
     */
    public static function arrayToQueryString(array $params): string
    {
        $query = '';
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $query .= '&'.urlencode($key.'[]').'='.urlencode($v);
                }
            } else {
                $query .= '&'.urlencode($key).'='.urlencode($value);
            }
        }

        return preg_replace('/&/', '?', $query, 1);
    }
}
