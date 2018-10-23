<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwaggerAssertions\JsonSchema\Uri\Retrievers;

use SwaggerAssertions\JsonSchema\Exception\ResourceNotFoundException;
use SwaggerAssertions\JsonSchema\Validator;

/**
 * Tries to retrieve JSON schemas from a URI using file_get_contents()
 *
 * @author Sander Coolen <sander@jibber.nl>
 */
class FileGetContents extends AbstractRetriever
{
    protected $messageBody;

    public function retrieve($uri): string
    {
        set_error_handler(function () use ($uri) {
            throw new ResourceNotFoundException('JSON schema not found at ' . $uri);
        });
        $response = file_get_contents($uri);
        restore_error_handler();

        if (false === $response) {
            throw new ResourceNotFoundException('JSON schema was not retrieved at ' . $uri);
        }

        if (
            $response === ''
            && strpos($uri,'file://') === 0
            && substr($uri, -1) === '/'
        ) {
            throw new ResourceNotFoundException('JSON schema not found at ' . $uri);
        }

        if (!empty($http_response_header)) {
            $this->fetchContentType($http_response_header);
        } else {
            // Could be a "file://" url or something else - fake up the response
            $this->contentType = null;
        }

        return $response;
    }

    /**
     * @param array $headers HTTP Response Headers
     *
     * @return boolean Whether the Content-Type header was found or not
     */
    private function fetchContentType(array $headers): bool
    {
        foreach ($headers as $header) {
            if ($this->contentType = self::getContentTypeMatchInHeader($header)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $header
     * @return string|null
     */
    protected static function getContentTypeMatchInHeader($header)
    {
        $match = null;
        if (0 < preg_match("/Content-Type:(\V*)/ims", $header, $match)) {
            return trim($match[1]);
        }
    }
}
