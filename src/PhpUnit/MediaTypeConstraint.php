<?php

namespace SwaggerAssertions\PhpUnit;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Validate given media type is present in the allowed media type collection.
 */
class MediaTypeConstraint extends Constraint
{
    /**
     * @var string[]
     */
    protected $allowedMediaTypes;

    /**
     * @param string[] $allowedMediaTypes Collection of allowed media types.
     */
    public function __construct(array $allowedMediaTypes)
    {
        parent::__construct();

        $this->allowedMediaTypes = $allowedMediaTypes;
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($other)
    {
        return \in_array($other, $this->allowedMediaTypes, true);
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'is an allowed media type (' . implode(', ', $this->allowedMediaTypes) . ')';
    }
}
