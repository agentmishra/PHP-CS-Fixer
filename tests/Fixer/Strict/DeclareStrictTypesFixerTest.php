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

namespace PhpCsFixer\Tests\Fixer\Strict;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;
use PhpCsFixer\WhitespacesFixerConfig;

/**
 * @author SpacePossum
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer
 */
final class DeclareStrictTypesFixerTest extends AbstractFixerTestCase
{
    private static $configurationAddMissing = ['add_missing' => true];
    private static $configurationRelocateNextLine = ['relocate_to' => 'next'];
    private static $configurationRelocateSameLine = ['relocate_to' => 'same'];

    /**
     * @param string      $expected
     * @param null|string $input
     * @param null|array  $configuration
     *
     * @dataProvider provideFixCases
     * @requires PHP 7.0
     */
    public function testDefaultFix($expected, $input = null, array $configuration = null)
    {
        if (null !== $configuration) {
            $this->fixer->configure($configuration);
        }

        $this->doTest($expected, $input);
    }

    public function provideDefaultFixCases()
    {
        return [
            [
                '<?php
declare(ticks=1);
//
declare(strict_types=1);

namespace A\B\C;
class A {
}',
            ],
            [
                '<?php
declare/* A b C*/(strict_types=1);',
            ],
            [
                '<?php /**/ /**/ deClarE  (strict_types=1)    ?>Test',
                '<?php /**/ /**/ deClarE  (STRICT_TYPES=1)    ?>Test',
            ],
            [
                '<?php            DECLARE  (    strict_types=1   )   ;',
            ],
            [
                '<?php
                /**/
                declare(strict_types=1);',
            ],
            [
                '<?php
declare(strict_types=1);

                phpinfo();',
                '<?php

                phpinfo();',
            ],
            [
                '<?php
declare(strict_types=1);

/**
 * Foo
 */
phpinfo();',
                '<?php

/**
 * Foo
 */
phpinfo();',
            ],
            [
                '<?php
declare(strict_types=1);
phpinfo();',
                '<?php phpinfo();',
            ],
            [
                '<?php
declare(strict_types=1);
$a = 456;
',
                '<?php
$a = 456;
',
            ],
            [
                '<?php
declare(strict_types=1);
/**/',
                '<?php /**/',
            ],
        ];
    }

    /**
     * @param string      $expected
     * @param null|string $input
     * @param null|array  $configuration
     *
     * @dataProvider provideNextLineFixCases
     * @requires PHP 7.0
     */
    public function testNextLineFix($expected, $input = null, array $configuration = null)
    {
        if (null !== $configuration) {
            $this->fixer->configure($configuration);
        }

        $this->doTest($expected, $input);
    }

    public function provideNextLineFixCases()
    {
        return [
            [
                '<?php
declare/* A b C*/(strict_types=1);
declare(ticks=1);
//


namespace A\B\C;
class A {
}',
                '<?php
declare(ticks=1);
//
declare/* A b C*/(strict_types=1);

namespace A\B\C;
class A {
}',
                self::$configurationRelocateNextLine
            ],
            [
                '<?php
declare/* A b C*/(strict_types=1);',
                null,
                self::$configurationRelocateNextLine
            ],
            [
                '<?php
deClarE(strict_types=1)
/**/ /**/       ?>Test',
                '<?php /**/ /**/ deClarE  (STRICT_TYPES=1)    ?>Test',
                self::$configurationRelocateNextLine
            ],
            [
                '<?php
DECLARE(strict_types=1);
                       ',
                '<?php            DECLARE  (    strict_types=1   )   ;',
                self::$configurationRelocateNextLine
            ],
            [
                '<?php
declare(strict_types=1);
                /**/
                ',
                '<?php
                /**/
                declare(strict_types=1);',
                self::$configurationRelocateNextLine
            ],
            [
                '<?php
declare(strict_types=1);

                phpinfo();',
                '<?php

                phpinfo();',
                self::$configurationRelocateNextLine
            ],
            [
                '<?php
declare(strict_types=1);

/**
 * Foo
 */
phpinfo();',
                '<?php

/**
 * Foo
 */
phpinfo();',
                self::$configurationRelocateNextLine
            ],
            [
                '<?php
declare(strict_types=1);
phpinfo();',
                '<?php phpinfo();',
                self::$configurationRelocateNextLine
            ],
            [
                '<?php
declare(strict_types=1);
$a = 456;
',
                '<?php
$a = 456;
',
                self::$configurationRelocateNextLine
            ],
            [
                '<?php
declare(strict_types=1);
/**/',
                '<?php /**/',
                self::$configurationRelocateNextLine
            ],
        ];
    }

    /**
     * @param string      $expected
     * @param null|string $input
     * @param null|array  $configuration
     *
     * @dataProvider provideSameLineFixCases
     * @requires PHP 7.0
     */
    public function testSameLineFix($expected, $input = null, array $configuration = null)
    {
        if (null !== $configuration) {
            $this->fixer->configure($configuration);
        }

        $this->doTest($expected, $input);
    }

    public function provideSameLineFixCases()
    {
        return [
            [
                '<?php declare/* A b C*/(strict_types=1);
declare(ticks=1);
//


namespace A\B\C;
class A {
}',
                '<?php declare(ticks=1);
//
declare/* A b C*/(strict_types=1);

namespace A\B\C;
class A {
}',
                self::$configurationRelocateSameLine
            ],
            [
                '<?php declare/* A b C*/(strict_types=1);',
                null,
                self::$configurationRelocateSameLine
            ],
            [
                '<?php deClarE(strict_types=1)
/**/ /**/       ?>Test',
                '<?php /**/ /**/ deClarE  (STRICT_TYPES=1)    ?>Test',
                self::$configurationRelocateSameLine
            ],
            [
                '<?php DECLARE(strict_types=1);
                       ',
                '<?php            DECLARE  (    strict_types=1   )   ;',
                self::$configurationRelocateSameLine
            ],
            [
                '<?php declare(strict_types=1);
                /**/
                ',
                '<?php
                /**/
                declare(strict_types=1);',
                self::$configurationRelocateSameLine
            ],
            [
                '<?php declare(strict_types=1);

                phpinfo();',
                '<?php

                phpinfo();',
                self::$configurationRelocateSameLine
            ],
            [
                '<?php declare(strict_types=1);

/**
 * Foo
 */
phpinfo();',
                '<?php

/**
 * Foo
 */
phpinfo();',
                self::$configurationRelocateSameLine
            ],
            [
                '<?php declare(strict_types=1);

// comment after empty line',
                '<?php

// comment after empty line',
            ],
            [
                '<?php declare(strict_types=1);
// comment without empty line before',
                '<?php
// comment without empty line before',
            ],
            [
                '<?php declare(strict_types=1);
phpinfo();',
                '<?php phpinfo();',
                self::$configurationRelocateSameLine
            ],
            [
                '<?php declare(strict_types=1);
$a = 456;
',
                '<?php
$a = 456;
',
                self::$configurationRelocateSameLine
            ],
            [
                '<?php declare(strict_types=1);
/**/',
                '<?php /**/',
                self::$configurationRelocateSameLine
            ],
            [
                '<?php declare(strict_types=1);',
                '<?php declare(strict_types=0);',
            ],
        ];
    }

    /**
     * @dataProvider provideDoNotFixCases
     */
    public function testDoNotFix(string $input): void
    {
        $this->doTest($input);
    }

    public function provideDoNotFixCases(): array
    {
        return [
            ['  <?php echo 123;'], // first statement must be a open tag
            ['<?= 123;'], // first token open with echo is not fixed
            ['<?php declare(strict_types=1);'] // declare statement made, no preference to position configured
        ];
    }

    /**
     * @dataProvider provideMessyWhitespacesCases
     */
    public function testMessyWhitespaces(string $expected, ?string $input = null): void
    {
        $this->fixer->setWhitespacesConfig(new WhitespacesFixerConfig("\t", "\r\n"));

        $this->doTest($expected, $input);
    }

    public function provideMessyWhitespacesCases(): array
    {
        return [
            [
                "<?php\r\ndeclare(strict_types=1);\r\n\tphpinfo();",
                "<?php\r\n\tphpinfo();",
            ],
            [
                "<?php\r\ndeclare(strict_types=1);\r\nphpinfo();",
                "<?php\nphpinfo();",
            ],
        ];
    }
}
