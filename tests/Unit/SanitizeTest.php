<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SanitizeTest extends TestCase
{
    public function test_sanitize_strips_tags(): void
    {
        $input = '<script>alert("xss")</script>Hello';
        $result = strip_tags($input);
        $this->assertEquals('Hello', $result);
    }

    public function test_sanitize_escapes_html(): void
    {
        $input = '<b>bold</b>';
        $result = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE);
        $this->assertEquals('&lt;b&gt;bold&lt;/b&gt;', $result);
    }

    public function test_sanitize_handles_quotes(): void
    {
        $input = 'He said "hello" & \'goodbye\'';
        $result = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE);
        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringContainsString('&#039;', $result);
    }

    public function test_sanitize_strips_slashes(): void
    {
        $input = "It\'s a test";
        $result = stripslashes($input);
        $this->assertEquals("It's a test", $result);
    }
}
