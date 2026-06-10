<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/AIClient.php';

class AIClientTest extends TestCase
{
    public function test_client_initialization(): void
    {
        $client = new AIClient('openai', 'test-key', 'gpt-4o-mini', 150, 0.5);
        $this->assertInstanceOf(AIClient::class, $client);
    }

    public function test_invalid_provider_fallback_failure(): void
    {
        // Assert that calling complete with an invalid URL/key throws RuntimeException
        $client = new AIClient('invalid-provider', 'test-key', 'gpt-4o-mini');
        $this->expectException(RuntimeException::class);
        $client->complete('system', 'user');
    }
}
