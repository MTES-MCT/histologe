<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\Request;

class UrlHelper
{
    private const int HTTP_PORT = 80;
    private const int HTTPS_PORT = 443;

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

    public static function buildRootUrl(string $path, ?Request $request = null): string
    {
        if (null === $request) {
            return $path;
        }

        $rootDomain = self::extractRootDomain($request->getHost());
        $port = \in_array($request->getPort(), [self::HTTP_PORT, self::HTTPS_PORT], true) ? '' : ':'.$request->getPort();

        return sprintf('%s://%s%s%s', $request->getScheme(), $rootDomain, $port, $path);
    }
}
