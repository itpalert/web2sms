<?php

namespace ITPalert\Web2sms\GsmCharsetConverter;

final class Converter
{
    /**
     * @var array<string, string>
     */
    private array $utf8ToGsm;

    /**
     * @var array<string, string>
     */
    private array $utf8ToGsmWithTranslit;

    /**
     * Converter constructor.
     */
    public function __construct()
    {
        // Flip the GSM to UTF-8 dictionary to create the UTF-8 to GSM dictionary;
        // Convert all values to strings as the array keys for digits are converted to int by PHP.
        $this->utf8ToGsm = array_map(
            static fn ($value) => (string) $value,
            array_flip(Charset::GSM_TO_UTF8)
        );

        // Create the base dictionary + transliteration
        $this->utf8ToGsmWithTranslit = $this->utf8ToGsm;

        foreach (Charset::TRANSLITERATE as $from => $to) {
            // Transliterate character by character, as the output string may contain several chars
            $to = $this->splitUtf8String($to);

            $to = array_map(fn (string $char) : string => $this->utf8ToGsm[$char], $to);

            $this->utf8ToGsmWithTranslit[$from] = implode('', $to);
        }
    }

    /**
     * Converts a UTF-8 string to GSM 03.38.
     *
     * The output is an unpacked 7-bit GSM charset string: the leading bit is zero in every byte.
     *
     * @param string      $string       The UTF-8 string to convert. If the string is not valid UTF-8, an exception
     *                                  is thrown.
     * @param bool        $translit     Whether to transliterate, i.e. replace incompatible characters with similar,
     *                                  compatible characters when possible.
     * @param string|null $replaceChars Zero or more UTF-8 characters to replace unknown chars with. You can typically
     *                                  use an empty string, a blank space or a question mark. The string must only
     *                                  contain characters compatible with the GSM charset, or an exception is thrown.
     *                                  If this parameter is omitted or null, and the string to convert contains any
     *                                  character that cannot be replaced, an exception is thrown.
     *
     * @throws \InvalidArgumentException If an error occurs.
     */
    public function convertUtf8ToGsm(string $string, bool $translit, ?string $replaceChars = null) : string
    {
        $dictionary = $translit ? $this->utf8ToGsmWithTranslit : $this->utf8ToGsm;

        // Convert the replacement string to GSM 03.38
        if ($replaceChars !== null) {
            $chars = $this->splitUtf8String($replaceChars);
            $replaceChars = '';

            foreach ($chars as $char) {
                if (! isset($this->utf8ToGsm[$char])) {
                    throw new \InvalidArgumentException(
                        'Replacement string must contain only GSM 03.38 compatible chars.'
                    );
                }

                $replaceChars .= $this->utf8ToGsm[$char];
            }
        }

        $result = '';

        $chars = $this->splitUtf8String($string);

        foreach ($chars as $char) {
            if (isset($dictionary[$char])) {
                $result .= $dictionary[$char];
            } elseif ($replaceChars !== null) {
                $result .= $replaceChars;
            } else {
                throw new \InvalidArgumentException(
                    'UTF-8 character ' . strtoupper(bin2hex($char)) . ' cannot be converted, ' .
                    'and no replacement string has been provided.'
                );
            }
        }

        return $result;
    }

     /**
     * @return string[]
     *
     * @throws \InvalidArgumentException
     */
    private function splitUtf8String(string $string) : array
    {
        if (! mb_check_encoding($string, 'UTF-8')) {
            throw new \InvalidArgumentException('The input string is not valid UTF-8.');
        }

        return preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
    }
}