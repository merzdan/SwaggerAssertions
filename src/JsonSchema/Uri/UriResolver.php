<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwaggerAssertions\JsonSchema\Uri;

use SwaggerAssertions\JsonSchema\Exception\UriResolverException;
use SwaggerAssertions\JsonSchema\UriResolverInterface;

/**
 * Resolves JSON Schema URIs
 * 
 * @author Sander Coolen <sander@jibber.nl> 
 */
class UriResolver implements UriResolverInterface
{
    /**
     * Parses a URI into five main components
     * 
     * @param string $uri
     * @return array 
     */
    public function parse($uri): array
    {
        $match = null;
        preg_match('|^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?|', $uri, $match);

        $components = [];
        if (5 < \count($match)) {
            $components = [
                'scheme'    => $match[2],
                'authority' => $match[4],
                'path'      => $match[5],
            ];
        } 
        if (isset($match[7])) {
            $components['query'] = $match[7];
        }
        if (isset($match[9])) {
            $components['fragment'] = $match[9];
        }
        
        return $components;
    }
    
    /**
     * Builds a URI based on n array with the main components
     * 
     * @param array $components
     * @return string 
     */
    public function generate(array $components): string
    {
        $uri = $components['scheme'] . '://' 
             . $components['authority']
             . $components['path'];
        
        if (array_key_exists('query', $components)) {
            $uri .= $components['query'];
        }
        if (array_key_exists('fragment', $components)) {
            $uri .= '#' . $components['fragment'];
        }
        
        return $uri;
    }
    
    /**
     * {@inheritdoc}
     */
    public function resolve($uri, $baseUri = null)
    {
        if ($uri === '') {
            return $baseUri;
        }

        $components = $this->parse($uri);
        $path       = $components['path'];
        
        if (!empty($components['scheme'])) {
            return $uri;
        }
        $baseComponents = $this->parse($baseUri);
        $basePath       = $baseComponents['path'];
        
        $baseComponents['path'] = self::combineRelativePathWithBasePath($path, $basePath);
        if (isset($components['fragment'])) {
            $baseComponents['fragment'] = $components['fragment'];
        }

        return $this->generate($baseComponents);
    }
    
    /**
     * Tries to glue a relative path onto an absolute one
     *
     * @param string $relativePath
     * @param string $basePath
     *
     * @return string Merged path
     *
     * @throws UriResolverException
     */
    public static function combineRelativePathWithBasePath($relativePath, $basePath)
    {
        $relativePath = self::normalizePath($relativePath);
        if ($relativePath === '') {
            return $basePath;
        }
        if (strpos($relativePath, '/') === 0) {
            return $relativePath;
        }

        $basePathSegments = explode('/', $basePath);

        preg_match('|^/?(\.\./(?:\./)*)*|', $relativePath, $match);
        $numLevelUp = \strlen($match[0]) /3 + 1;
        if ($numLevelUp >= \count($basePathSegments)) {
            throw new UriResolverException(sprintf("Unable to resolve URI '%s' from base '%s'", $relativePath, $basePath));
        }

        $basePathSegments = \array_slice($basePathSegments, 0, -$numLevelUp);
        $path = preg_replace('|^/?(\.\./(\./)*)*|', '', $relativePath);

        return implode('/', $basePathSegments) . '/' . $path;
    }

    /**
     * Normalizes a URI path component by removing dot-slash and double slashes
     *
     * @param string $path
     * @return string
     */
    private static function normalizePath($path): string
    {
        $path = preg_replace('|((?<!\.)\./)*|', '', $path);
        $path = preg_replace('|//|', '/', $path);
        
        return $path;
    }
    
    /**
     * @param string $uri
     * @return boolean 
     */
    public function isValid($uri): bool
    {
        $components = $this->parse($uri);
        
        return !empty($components);
    }
}
