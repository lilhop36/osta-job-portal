<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('hash_password')) {
            require_once __DIR__ . '/../../includes/security.php';
        }
    }

    public function testHashPasswordReturnsString(): void
    {
        $hash = hash_password('TestPassword123!');
        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    public function testVerifyPasswordAcceptsCorrectPassword(): void
    {
        $password = 'PortfolioReady#2026';
        $hash = hash_password($password);
        $this->assertTrue(verify_password($password, $hash));
    }

    public function testVerifyPasswordRejectsWrongPassword(): void
    {
        $hash = hash_password('CorrectPassword');
        $this->assertFalse(verify_password('WrongPassword', $hash));
    }

    public function testHashPasswordProducesArgon2Hash(): void
    {
        $hash = hash_password('test');
        $this->assertStringStartsWith('$argon2id$', $hash);
    }

    public function testSanitizeInputRemovesHtmlTags(): void
    {
        $result = sanitize_input('<script>alert("xss")</script>Hello');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    public function testSanitizeInputTrimsWhitespace(): void
    {
        $result = sanitize_input('  hello  ');
        $this->assertSame('hello', $result);
    }

    public function testSanitizeInputHandlesArrays(): void
    {
        $result = sanitize_input(['  a  ', '  b  ']);
        $this->assertSame(['a', 'b'], $result);
    }

    public function testGenerateSecurePasswordReturnsCorrectLength(): void
    {
        $password = generate_secure_password(16);
        $this->assertSame(16, strlen($password));
    }

    public function testGenerateSecurePasswordDefaultLength(): void
    {
        $password = generate_secure_password();
        $this->assertSame(12, strlen($password));
    }
}
