<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwaggerAssertions\JsonSchema\Uri;

use SwaggerAssertions\JsonSchema\Uri\Retrievers\FileGetContents;
use SwaggerAssertions\JsonSchema\Uri\Retrievers\UriRetrieverInterface;
use SwaggerAssertions\JsonSchema\UriRetrieverInterface as BaseUriRetrieverInterface;
use SwaggerAssertions\JsonSchema\Validator;
use SwaggerAssertions\JsonSchema\Exception\InvalidSchemaMediaTypeException;
use SwaggerAssertions\JsonSchema\Exception\JsonDecodingException;
use SwaggerAssertions\JsonSchema\Exception\ResourceNotFoundException;

/**
 * Retrieves JSON Schema URIs
 *
 * @author Tyler Akins <fidian@rumkin.com>
 */
class UriRetriever implements BaseUriRetrieverInterface
{
    /**
     * @var null|UriRetrieverInterface
     */
    protected $uriRetriever;


    private $path;

    /**
     * @var array|object[]
     * @see loadSchema
     */
    private $schemaCache = [];


    public function setPath(string $path){
        $this->path = $path;

        return $this;
    }

    /**
     * Guarantee the correct media type was encountered
     *
     * @param UriRetrieverInterface $uriRetriever
     * @param string $uri
     * @return bool|void
     */
    public function confirmMediaType($uriRetriever, $uri)
    {
        $contentType = $uriRetriever->getContentType();

        if (null === $contentType) {
            // Well, we didn't get an invalid one
            return;
        }

        if (\in_array($contentType, [Validator::SCHEMA_MEDIA_TYPE, 'application/json'], true)) {
            return;
        }

        if (strpos($uri, 'http://json-schema.org/') === 0) {
            //HACK; they deliver broken content types
            return true;
        }

        throw new InvalidSchemaMediaTypeException(sprintf('Media type %s expected', Validator::SCHEMA_MEDIA_TYPE));
    }

    /**
     * Get a URI Retriever
     *
     * If none is specified, sets a default FileGetContents retriever and
     * returns that object.
     *
     * @return UriRetrieverInterface
     */
    public function getUriRetriever(): UriRetrieverInterface
    {
        if (null === $this->uriRetriever) {
            $this->setUriRetriever(new FileGetContents);
        }

        return $this->uriRetriever;
    }

    /**
     * Resolve a schema based on pointer
     *
     * URIs can have a fragment at the end in the format of
     * #/path/to/object and we are to look up the 'path' property of
     * the first object then the 'to' and 'object' properties.
     *
     * @param object $jsonSchema JSON Schema contents
     * @param string $uri JSON Schema URI
     * @return object JSON Schema after walking down the fragment pieces
     *
     * @throws ResourceNotFoundException
     */
    public function resolvePointer($jsonSchema, $uri)
    {
        $resolver = new UriResolver();
        $parsed = $resolver->parse($uri);
        if (empty($parsed['fragment'])) {
            return $jsonSchema;
        }

        $path = explode('/', $parsed['fragment']);
        while ($path) {
            $pathElement = array_shift($path);
            if (! empty($pathElement)) {
                $pathElement = str_replace('~1', '/', $pathElement);
                $pathElement = str_replace('~0', '~', $pathElement);
                if (! empty($jsonSchema->$pathElement)) {
                    $jsonSchema = $jsonSchema->$pathElement;
                } else {
                    throw new ResourceNotFoundException(
                        'Fragment "' . $parsed['fragment'] . '" not found'
                        . ' in ' . $uri
                    );
                }

                if (!\is_object($jsonSchema)) {
                    throw new ResourceNotFoundException(
                        'Fragment part "' . $pathElement . '" is no object '
                        . ' in ' . $uri
                    );
                }
            }
        }

        return $jsonSchema;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveSkeleton($uri, $baseUri = null)
    {
        $resolver = new UriResolver();
        $resolvedUri = $fetchUri = $resolver->resolve($uri, $baseUri);

        $jsonSchema = $this->loadSchema($fetchUri);

        $paths = array_flip(array_keys((array)$jsonSchema->paths));
        unset($jsonSchema->paths, $jsonSchema->definitions);
        $jsonSchema->paths = (object)$paths;

        if ($jsonSchema instanceof \stdClass) {
            $jsonSchema->id = $resolvedUri;
        }

        return $jsonSchema;
    }

    public function retrievePath($uri, $path, $baseUri = null)
    {
        $resolver = new UriResolver();
        $fetchUri = $resolver->resolve($uri, $baseUri);

        $jsonSchema = $this->loadSchema($fetchUri);

        $pathNode = $jsonSchema->paths->{$path};
        unset($jsonSchema->paths);
        $jsonSchema->paths = json_decode(json_encode([
            $path => $pathNode
        ]));

        return $jsonSchema;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve($uri, $baseUri = null)
    {
        $resolver = new UriResolver();
        $resolvedUri = $fetchUri = $resolver->resolve($uri, $baseUri);

        //fetch URL without #fragment
        $arParts = $resolver->parse($resolvedUri);
        if (isset($arParts['fragment'])) {
            unset($arParts['fragment']);
            $fetchUri = $resolver->generate($arParts);
        }

        if (isset($this->schemaCache[$fetchUri])) {
            $jsonSchema =  $this->schemaCache[$fetchUri];
        } else {
            $jsonSchema = $this->loadSchema($fetchUri);
            $this->schemaCache[$fetchUri] = $jsonSchema;
        }

        // Use the JSON pointer if specified
        $jsonSchema = $this->resolvePointer($jsonSchema, $resolvedUri);

        if ($jsonSchema instanceof \stdClass) {
            $jsonSchema->id = $resolvedUri;
        }

        return $jsonSchema;
    }

    /**
     * Fetch a schema from the given URI, json-decode it and return it.
     * Caches schema objects.
     *
     * @param string $fetchUri Absolute URI
     *
     * @return object JSON schema object
     */
    protected function loadSchema($fetchUri)
    {


        $uriRetriever = $this->getUriRetriever();
        $contents = $this->uriRetriever->retrieve($fetchUri);
        $this->confirmMediaType($uriRetriever, $fetchUri);
        $jsonSchema = json_decode($contents);

        if (JSON_ERROR_NONE < $error = json_last_error()) {
            throw new JsonDecodingException($error);
        }

        return $jsonSchema;
    }

    /**
     * Set the URI Retriever
     *
     * @param UriRetrieverInterface $uriRetriever
     * @return $this for chaining
     */
    public function setUriRetriever(UriRetrieverInterface $uriRetriever): self
    {
        $this->uriRetriever = $uriRetriever;

        return $this;
    }

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

        $components = array();
        if (5 < \count($match)) {
            $components =  array(
                'scheme'    => $match[2],
                'authority' => $match[4],
                'path'      => $match[5]
            );
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
            $uri .= $components['fragment'];
        }

        return $uri;
    }

    /**
     * Resolves a URI
     *
     * @param string $uri Absolute or relative
     * @param string $baseUri Optional base URI
     * @return string
     */
    public function resolve($uri, $baseUri = null): string
    {
        $components = $this->parse($uri);
        $path       = $components['path'];

        if (array_key_exists('scheme', $components) && ('http' === $components['scheme'])) {
            return $uri;
        }

        $baseComponents = $this->parse($baseUri);
        $basePath       = $baseComponents['path'];

        $baseComponents['path'] = UriResolver::combineRelativePathWithBasePath($path, $basePath);

        return $this->generate($baseComponents);
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
