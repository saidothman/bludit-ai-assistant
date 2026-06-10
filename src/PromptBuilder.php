<?php

/**
 * PromptBuilder
 *
 * Centralises all prompt construction so they are easy to test,
 * tune, and extend independently from the HTTP layer.
 */
class PromptBuilder
{
    private const SYSTEM = 'You are an expert SEO copywriter and content strategist. '
        . 'Respond with only the requested output — no preamble, no explanation.';

    /**
     * @return array{system: string, user: string}
     */
    public static function titles(string $content): array
    {
        return [
            'system' => self::SYSTEM,
            'user'   => "Generate exactly 3 compelling, SEO-optimised blog post titles for the following content. "
                      . "Return them as a numbered list (1. 2. 3.), one per line, no extra text.\n\n"
                      . self::truncate($content),
        ];
    }

    /**
     * @return array{system: string, user: string}
     */
    public static function metaDescription(string $content): array
    {
        return [
            'system' => self::SYSTEM,
            'user'   => "Write a single meta description for the following content. "
                      . "It must be between 150 and 160 characters, include a call-to-action, "
                      . "and end without a period.\n\n"
                      . self::truncate($content),
        ];
    }

    /**
     * @return array{system: string, user: string}
     */
    public static function keywords(string $content): array
    {
        return [
            'system' => self::SYSTEM,
            'user'   => "Extract exactly 8 SEO keywords from the following content. "
                      . "Mix short-tail and long-tail keywords. "
                      . "Return them as a comma-separated list, no numbering, no explanation.\n\n"
                      . self::truncate($content),
        ];
    }

    /** Prevent sending huge payloads to the API — ~800 words max */
    private static function truncate(string $text, int $chars = 3000): string
    {
        $clean = strip_tags($text);
        return mb_strlen($clean) > $chars
            ? mb_substr($clean, 0, $chars) . '…'
            : $clean;
    }
}
