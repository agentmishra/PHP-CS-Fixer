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

namespace PhpCsFixer\Fixer\Strict;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author SpacePossum
 * @author Aidan Woods
 */
final class DeclareStrictTypesFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    /**
     * @internal
     */
    const LINE_NEXT = 'next';

    /**
     * @internal
     */
    const LINE_SAME = 'same';

    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Force strict types declaration in all files. Requires PHP >= 7.0.',
            [
                new CodeSample(
                    "<?php\n"
                ),
            ],
            null,
            'Forcing strict types will stop non strict code from working.'
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before BlankLineAfterOpeningTagFixer, DeclareEqualNormalizeFixer, HeaderCommentFixer.
     */
    public function getPriority(): int
    {
        return 2;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return isset($tokens[0]) && $tokens[0]->isGivenKind(T_OPEN_TAG);
    }

    /**
     * {@inheritdoc}
     */
    public function isRisky(): bool
    {
        return true;
    }

    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('add_missing', 'Whether to add missing ``declare(strict_types=1)`` to file, and to correct casing.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
            (new FixerOptionBuilder('relocate_to', 'Whether ``declare(strict_types=1)`` should be placed on "next" or "same" line, after the opening ``<?php`` tag, or false if ``declare(strict_types=1)`` should not be moved.'))
                ->setAllowedValues([self::LINE_NEXT, self::LINE_SAME, false])
                ->setDefault(false)
                ->getOption(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        // check if the declaration is already done
        $searchIndex = $tokens->getNextMeaningfulToken(0);
        $sequence = $this->getDeclareStrictTypeSequence();
        
        if (null !== $sequence && null != $searchIndex) {
			$sequenceLocation = $tokens->findSequence($sequence, $searchIndex, null, false);
		}

		if (null !== $this->configuration) {
			if (null === $searchIndex) {
				if ($this->configuration['add_missing']) {
					$this->insertSequence($tokens); // declaration not found, insert one
				}
			} elseif (null === $sequenceLocation) {
				if ($this->configuration['add_missing']) {
					$this->insertSequence($tokens); // declaration not found, insert one
				}
			} elseif ($this->configuration['add_missing']) {
				$this->fixStrictTypesCasing($tokens, $sequenceLocation);
			}
		}

        // // check if the declaration is already done
        $searchIndex = $tokens->getNextMeaningfulToken(0);
        if (null === $searchIndex) {
            return;
        }

        // do not look for open tag, closing semicolon or empty lines;
        // - open tag is tested by isCandidate
        // - semicolon or end tag must be there to be valid PHP
        // - empty tokens and comments are dealt with later
        if (null === $sequence) {
            $sequence = [
                new Token([T_DECLARE, 'declare']),
                new Token('('),
                new Token([T_STRING, 'strict_types']),
                new Token('='),
                new Token([T_LNUMBER, '1']),
                new Token(')'),
            ];
        }
        if (null !== $sequenceLocation && false !== $this->configuration['relocate_to']) {
            $this->fixLocation($tokens, $sequenceLocation);
        }

        $this->fixStrictTypesCasingAndValue($tokens, $sequenceLocation);
    }

    /**
     * @param array<int, Token> $sequence
     */
    private function fixStrictTypesCasingAndValue(Tokens $tokens, array $sequence): void
    {
        /** @var int $index */
        /** @var Token $token */
        foreach ($sequence as $index => $token) {
            if ($token->isGivenKind(T_STRING)) {
                $tokens[$index] = new Token([T_STRING, strtolower($token->getContent())]);

                continue;
            }
            if ($token->isGivenKind(T_LNUMBER)) {
                $tokens[$index] = new Token([T_LNUMBER, '1']);

                break;
            }
        }
    }

    private function insertSequence(Tokens $tokens): void
    {
        // ensure there is a newline after php open tag
        $lineEnding = $this->whitespacesConfig->getLineEnding();
        $tokens[0] = new Token([$tokens[0]->getId(), rtrim($tokens[0]->getContent()).$lineEnding]);

        if (null === $sequence) {
            $sequence = $this->getDeclareStrictTypeSequence();
            $sequence[] = new Token(';');
        }

        $endIndex = count($sequence);

        $tokens->insertAt(1, $sequence);

        // start index of the sequence is always 1 here, 0 is always open tag
        // transform "<?php\n" to "<?php " if needed
        if (false !== strpos($tokens[0]->getContent(), "\n")) {
            $tokens[0] = new Token([$tokens[0]->getId(), trim($tokens[0]->getContent()).' ']);
        }

        if ($endIndex === \count($tokens) - 1) {
            return; // no more tokens afters sequence, single_blank_line_at_eof might add a line
        }

        $nextToken = $tokens[$endIndex + 1];
        $nextLine = $nextToken->getContent();
        $trailingContent = ltrim($nextLine);
        $extraWhitespace = substr($nextLine, 0, strlen($nextLine) - strlen($trailingContent));

        $tokens->ensureWhitespaceAtIndex($endIndex + 1, 0, $lineEnding.$extraWhitespace);
    }

    /**
     * @param Tokens            $tokens
     * @param array<int, Token> $sequence
     */
    private function fixLocation(Tokens $tokens, array $sequence)
    {
        reset($sequence);
        $start = key($sequence);
        end($sequence);
        $end = key($sequence);

        $lineEnding = $this->whitespacesConfig->getLineEnding();

        if (1 !== $start) {
            $seq = [];
            for ($i = $start; $i <= $end; ++$i) {
                $seq[$i] = clone $tokens[$i];
                $tokens->clearTokenAndMergeSurroundingWhitespace($i);
            }

            $sequence = $seq;

            $tokens->clearEmptyTokens();

            $this->insertSequence($tokens, array_values(array_filter($sequence, function ($token) {return $token->getContent() !== ''; })));
        }

        $end = self::LINE_NEXT === $this->configuration['relocate_to'] ? $lineEnding : ' ';
        $tokens[0] = new Token([$tokens[0]->getId(), rtrim($tokens[0]->getContent()).$end]);
    }
    
    /**
     * @return Token[]
     */
    public function getDeclareStrictTypeSequence()
    {
        static $sequence = null;

        // do not look for open tag, closing semicolon or empty lines;
        // - open tag is tested by isCandidate
        // - semicolon or end tag must be there to be valid PHP
        // - empty tokens and comments are dealt with later
        if (null === $sequence) {
            $sequence = [
                new Token([T_DECLARE, 'declare']),
                new Token('('),
                new Token([T_STRING, 'strict_types']),
                new Token('='),
                new Token([T_LNUMBER, '1']),
                new Token(')'),
            ];
        }

        return $sequence;
    }
}
