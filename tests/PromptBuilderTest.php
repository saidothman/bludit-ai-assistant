<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/PromptBuilder.php';

class PromptBuilderTest extends TestCase
{
    private string $sampleContent = 'Dental hygiene is essential for overall health. '
        . 'Regular brushing, flossing, and professional cleanings prevent cavities and gum disease.';

    public function test_titles_returns_system_and_user_keys(): void
    {
        $prompt = PromptBuilder::titles($this->sampleContent);

        $this->assertArrayHasKey('system', $prompt);
        $this->assertArrayHasKey('user',   $prompt);
    }

    public function test_titles_user_prompt_contains_content(): void
    {
        $prompt = PromptBuilder::titles($this->sampleContent);

        $this->assertStringContainsString('Dental hygiene', $prompt['user']);
    }

    public function test_titles_requests_exactly_3_titles(): void
    {
        $prompt = PromptBuilder::titles($this->sampleContent);

        $this->assertStringContainsString('3', $prompt['user']);
    }

    public function test_meta_description_prompt_specifies_character_limit(): void
    {
        $prompt = PromptBuilder::metaDescription($this->sampleContent);

        $this->assertStringContainsString('150', $prompt['user']);
        $this->assertStringContainsString('160', $prompt['user']);
    }

    public function test_keywords_requests_exactly_8_keywords(): void
    {
        $prompt = PromptBuilder::keywords($this->sampleContent);

        $this->assertStringContainsString('8', $prompt['user']);
    }

    public function test_long_content_is_truncated(): void
    {
        $longContent = str_repeat('word ', 2000); // ~10,000 chars
        $prompt      = PromptBuilder::titles($longContent);

        // The user prompt should not contain the full content
        $this->assertLessThan(5000, mb_strlen($prompt['user']));
    }

    public function test_html_tags_are_stripped(): void
    {
        $htmlContent = '<p>Hello <strong>world</strong></p><script>alert(1)</script>';
        $prompt      = PromptBuilder::titles($htmlContent);

        $this->assertStringNotContainsString('<p>',      $prompt['user']);
        $this->assertStringNotContainsString('<strong>', $prompt['user']);
        $this->assertStringNotContainsString('<script>', $prompt['user']);
        $this->assertStringContainsString('Hello world', $prompt['user']);
    }
}
