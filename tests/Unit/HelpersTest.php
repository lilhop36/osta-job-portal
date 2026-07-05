<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('ROLE_ADMIN')) {
            require_once __DIR__ . '/../../includes/helpers.php';
        }
    }

    public function testAppBasePathReturnsString(): void
    {
        $result = app_base_path();
        $this->assertIsString($result);
    }

    public function testSafeRedirectTargetBlocksExternalUrls(): void
    {
        $this->assertSame('index.php', safe_redirect_target('https://evil.com', 'index.php'));
    }

    public function testSafeRedirectTargetBlocksProtocolRelative(): void
    {
        $this->assertSame('index.php', safe_redirect_target('//evil.com', 'index.php'));
    }

    public function testSafeRedirectTargetAllowsInternalPaths(): void
    {
        $this->assertSame('/osta%20job%20portal/applicant/dashboard.php', safe_redirect_target('/osta%20job%20portal/applicant/dashboard.php'));
    }

    public function testSafeRedirectTargetAllowsRelativePaths(): void
    {
        $this->assertSame('applicant/dashboard.php', safe_redirect_target('applicant/dashboard.php'));
    }

    public function testIsSafeInternalRedirectReturnsBool(): void
    {
        $this->assertIsBool(is_safe_internal_redirect('/test'));
    }

    public function testCleanDownloadFilenameRemovesPathTraversal(): void
    {
        $result = clean_download_filename('../../etc/passwd');
        $this->assertStringNotContainsString('..', $result);
        $this->assertStringNotContainsString('/', $result);
    }

    public function testCleanDownloadFilenamePreservesExtension(): void
    {
        $result = clean_download_filename('resume.pdf');
        $this->assertStringContainsString('resume', $result);
        $this->assertStringContainsString('.pdf', $result);
    }

    public function testCleanDownloadFilenameHandlesEmptyInput(): void
    {
        $result = clean_download_filename('');
        $this->assertNotEmpty($result);
    }

    public function testRoleConstantsAreDefined(): void
    {
        $this->assertSame('admin', ROLE_ADMIN);
        $this->assertSame('employer', ROLE_EMPLOYER);
        $this->assertSame('applicant', ROLE_APPLICANT);
    }
}
