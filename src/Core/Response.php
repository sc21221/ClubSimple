<?php

declare(strict_types=1);

namespace Clubmanager\Core;

/**
 * HTTP Response Handler
 *
 * @package Clubmanager\Core
 * @author  Reinhard Schiessl
 */
class Response
{
    /**
     * Send a JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @param array<string, string> $headers
     * @return void
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirect to another URL.
     *
     * @param string $url
     * @param int $status
     * @return void
     */
    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    /**
     * Send direct binary or file download response.
     *
     * @param string $content Raw content
     * @param string $filename
     * @param string $contentType
     * @return void
     */
    public static function downloadContent(string $content, string $filename, string $contentType = 'application/octet-stream'): void
    {
        // Clean any output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code(200);
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }
}
