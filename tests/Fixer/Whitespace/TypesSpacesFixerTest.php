<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Fixer\Whitespace;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\Whitespace\TypesSpacesFixer
 */
final class TypesSpacesFixerTest extends AbstractFixerTestCase
{
    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, array $configuration = []): void
    {
        $this->fixer->configure($configuration);
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): \Generator
    {
        yield [
            '<?php try {} catch (ErrorA|ErrorB $e) {}',
            '<?php try {} catch (ErrorA | ErrorB $e) {}',
        ];

        yield [
            '<?php try {} catch (ErrorA|ErrorB $e) {}',
            '<?php try {} catch (ErrorA    |    ErrorB $e) {}',
        ];

        yield [
            '<?php try {} catch (ErrorA | ErrorB $e) {}',
            '<?php try {} catch (ErrorA|ErrorB $e) {}',
            ['space' => 'single'],
        ];

        yield [
            '<?php try {} catch (ErrorA | ErrorB $e) {}',
            '<?php try {} catch (ErrorA    |    ErrorB $e) {}',
            ['space' => 'single'],
        ];
    }

    /**
     * @dataProvider provideFix80Cases
     * @requires PHP 8.0
     */
    public function testFix80(string $expected, ?string $input = null, array $configuration = []): void
    {
        $this->fixer->configure($configuration);
        $this->doTest($expected, $input);
    }

    public function provideFix80Cases(): \Generator
    {
        yield [
            '<?php function foo(TypeA|TypeB $x) {}',
            '<?php function foo(TypeA | TypeB $x) {}',
        ];
        yield [
            '<?php function foo(TypeA|TypeB|TypeC|TypeD $x, TypeE|TypeF $y, TypeA|TypeB $z) {}',
            '<?php function foo(TypeA | TypeB    |    TypeC | TypeD $x, TypeE    |    TypeF $y, TypeA| TypeB $z) {}',
        ];

        yield [
            '<?php function foo(): TypeA|TypeB {}',
            '<?php function foo(): TypeA | TypeB {}',
        ];

        yield [
            '<?php class Foo { private array|int $x; }',
            '<?php class Foo { private array | int $x; }',
        ];

        yield [
            '<?php function foo(TypeA
                |
                TypeB $x) {}',
        ];

        yield [
            '<?php function foo(TypeA
                |
                TypeB $x) {}',
            null,
            ['space' => 'single'],
        ];

        yield [
            '<?php function foo(TypeA/* not a space */|/* not a space */TypeB $x) {}',
        ];

        yield [
            '<?php function foo(TypeA/* not a space */ | /* not a space */TypeB $x) {}',
            '<?php function foo(TypeA/* not a space */|/* not a space */TypeB $x) {}',
            ['space' => 'single'],
        ];

        yield [
            '<?php function foo(TypeA// not a space
|//not a space
TypeB $x) {}',
        ];

        yield [
            '<?php function foo(TypeA// not a space
| //not a space
TypeB $x) {}',
            '<?php function foo(TypeA// not a space
|//not a space
TypeB $x) {}',
            ['space' => 'single'],
        ];

        yield [
            '<?php class Foo {
                public function __construct(
                    public int|string $a,
                    protected int|string $b,
                    private int|string $c
                ) {}
            }',
            '<?php class Foo {
                public function __construct(
                    public int    |    string $a,
                    protected int | string $b,
                    private int   |   string $c
                ) {}
            }',
        ];
    }
}
