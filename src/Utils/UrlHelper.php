<?php

namespace App\Utils;

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

    public static function extractRootDomain(string $host): string
    {
        $backofficeSubdomains = ['bo', 'service-secours'];
        $parts = explode('.', $host);

        if (in_array(strtolower($parts[0]), $backofficeSubdomains, true)) {
            return implode('.', array_slice($parts, 1));
        }

        return $host;
    }
}
