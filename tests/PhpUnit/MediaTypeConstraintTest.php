<?php

namespace SwaggerAssertionsTest\PhpUnit;

use SwaggerAssertions\PhpUnit\MediaTypeConstraint;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestFailure;

/**
 * @covers MediaTypeConstraint
 */
class MediaTypeConstraintTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\Constraint\Constraint
     */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new MediaTypeConstraint(['application/json', 'text/xml']);
    }

    public function testConstraintDefinition()
    {
        self::assertEquals(1, count($this->constraint));
        self::assertEquals('is an allowed media type (application/json, text/xml)', $this->constraint->toString());
    }

    public function testValidMediaType()
    {
        self::assertTrue($this->constraint->evaluate('text/xml', '', true));
    }

    public function testInvalidMediaType()
    {
        $mediaType = 'application/pdf';
        self::assertFalse($this->constraint->evaluate($mediaType, '', true));

        try {
            $this->constraint->evaluate($mediaType);
            self::fail('Expected ExpectationFailedException to be thrown');
        } catch (ExpectationFailedException $e) {
            self::assertEquals(
                <<<EOF
Failed asserting that 'application/pdf' is an allowed media type (application/json, text/xml).

EOF
                ,
                TestFailure::exceptionToString($e)
            );
        }
    }
}
