<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\UtilsBundle\Security;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\System;
use HeimrichHannot\UtilsBundle\String\StringUtil;
use PWGen\PWGen;

class CodeUtil
{
    const CAPITAL_LETTERS = 'capitalLetters';
    const SMALL_LETTERS = 'smallLetters';
    const NUMBERS = 'numbers';
    const SPECIAL_CHARS = 'specialChars';

    const DEFAULT_ALPHABETS = [
        self::CAPITAL_LETTERS,
        self::SMALL_LETTERS,
        self::NUMBERS,
    ];

    const DEFAULT_RULES = [
        self::CAPITAL_LETTERS,
        self::SMALL_LETTERS,
        self::NUMBERS,
    ];

    const DEFAULT_ALLOWED_SPECIAL_CHARS = '[=<>()#/]';
    /** @var ContaoFrameworkInterface */
    protected $framework;

    protected static $blnPreventAmbiguous = true;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Generates a code by certain criteria.
     *
     * @param int         $length
     * @param bool        $preventAmbiguous
     * @param array|null  $alphabets
     * @param array|null  $rules
     * @param string|null $allowedSpecialChars
     *
     * @return mixed
     */
    public static function generate(
        int $length = 8,
        bool $preventAmbiguous = true,
        array $alphabets = null,
        array $rules = null,
        string $allowedSpecialChars = null
    ) {
        $stringUtil = System::getContainer()->get('huh.utils.string');

        $alphabets = \is_array($alphabets) ? $alphabets : static::DEFAULT_ALPHABETS;
        $rules = \is_array($rules) ? $rules : static::DEFAULT_RULES;
        $allowedSpecialChars = null !== $allowedSpecialChars ? $allowedSpecialChars : static::DEFAULT_ALLOWED_SPECIAL_CHARS;

        $pwGen = new PWGen($length, false, \in_array(static::NUMBERS, $alphabets, true) && \in_array(static::NUMBERS, $rules, true), \in_array(static::CAPITAL_LETTERS, $alphabets, true) && \in_array(static::CAPITAL_LETTERS, $rules, true), $preventAmbiguous, false, \in_array(static::SPECIAL_CHARS, $alphabets, true) && \in_array(static::SPECIAL_CHARS, $rules, true));

        $code = $pwGen->generate();

        // replace remaining ambiguous characters
        if ($preventAmbiguous) {
            $charReplacements = ['y', 'Y', 'z', 'Z', 'o', 'O', 'i', 'I', 'l'];

            foreach ($charReplacements as $char) {
                $code = str_replace($char, $stringUtil->randomChar(!$preventAmbiguous), $code);
            }
        }

        // apply allowed alphabets
        $forbiddenPattern = '';
        $allowedChars = '';

        if (!\in_array(static::CAPITAL_LETTERS, $alphabets, true)) {
            $forbiddenPattern .= 'A-Z';
        } else {
            $allowedChars .= ($preventAmbiguous ? StringUtil::CAPITAL_LETTERS_NONAMBIGUOUS : StringUtil::CAPITAL_LETTERS);
        }

        if (!\in_array(static::SMALL_LETTERS, $alphabets, true)) {
            $forbiddenPattern .= 'a-z';
        } else {
            $allowedChars .= ($preventAmbiguous ? StringUtil::SMALL_LETTERS_NONAMBIGUOUS : StringUtil::SMALL_LETTERS);
        }

        if (!\in_array(static::NUMBERS, $alphabets, true)) {
            $forbiddenPattern .= '0-9';
        } else {
            $allowedChars .= ($preventAmbiguous ? StringUtil::NUMBERS_NONAMBIGUOUS : StringUtil::NUMBERS);
        }

        if ('' === $allowedChars) {
            return $code;
        }

        if ($forbiddenPattern) {
            $code = preg_replace_callback('@['.$forbiddenPattern.']{1}@', function () use ($allowedChars, $stringUtil) {
                return $stringUtil->random($allowedChars);
            }, $code);
        }

        // special chars
        if (!\in_array(static::SPECIAL_CHARS, $alphabets, true)) {
            $code = preg_replace_callback('@[^'.$allowedChars.']{1}@', function () use ($allowedChars, $stringUtil) {
                return $stringUtil->random($allowedChars);
            }, $code);
        } else {
            $code = preg_replace_callback('@[^'.$allowedChars.']{1}@', function () use ($allowedSpecialChars, $stringUtil) {
                return $stringUtil->random($allowedSpecialChars);
            }, $code);
        }

        return $code;
    }
}
