<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwaggerAssertions\JsonSchema\Uri\Retrievers;

/**
 * Interface for URI retrievers
 * 
 * @author Sander Coolen <sander@jibber.nl> 
 */
interface UriRetrieverInterface
{
    /**
     * Retrieve a schema from the specified URI
     * @param string $uri URI that resolves to a JSON schema
     * @throws SwaggerAssertions\JsonSchema\Exception\ResourceNotFoundException
     * @return mixed string|null
     */
    public function retrieve($uri): string;
    
    /**
     * Get media content type
     * @return string
     */
    public function getContentType();
}