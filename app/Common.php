<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter4.github.io/CodeIgniter4/
 */


/**
 * Returns the base URL as defined by the App config.
 * Base URLs are trimmed site URLs without the index page.
 *
 * @param array|string $relativePath URI string or array of URI segments
 * @param string|null  $scheme       URI scheme. E.g., http, ftp
 */
function assets_url($relativePath = '', ?string $scheme = null): string
{
    $relativePath = "assets/" . $relativePath;
    return base_url($relativePath, $scheme);
}

function tryDecodeData($value, $as_object = true)
{
    /**
     * Tests, if the given $value parameter is a JSON string.
     * When it is a valid JSON value, the decoded value is returned.
     * When the value is no JSON value (i.e. it was decoded already), then 
     * the original value is returned.
     */
    if (is_numeric($value)) {
        return 0 + $value;
    }
    if (!is_string($value)) {
        return $value;
    }
    if (strlen($value) < 2) {
        return $value;
    }
    if ('null' === $value) {
        return null;
    }
    if ('true' === $value) {
        return true;
    }
    if ('false' === $value) {
        return false;
    }
    if ('{' != $value[0] && '[' != $value[0] && '"' != $value[0]) {
        return $value;
    }

    $json_data = json_decode($value, $as_object);
    if (is_null($json_data)) {
        return $value;
    }
    return $json_data;
}

function urlOrHastag($relativePath = '', ?string $scheme = null)
{
    return preg_replace("/[\/#]+$/", "", base_url($relativePath, $scheme)) == getCurrentUrl() ?
        "#" : base_url($relativePath, $scheme);
}

function getCurrentUrl(): string
{
    return str_replace("/index.php", "", current_url());
}

function isInternalUrl(string $url): bool
{
    return strpos($url, base_url()) !== false;
}
