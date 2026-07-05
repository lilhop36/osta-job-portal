<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Services\DocumentService;

class DocumentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('App\Services\DocumentService')) {
            require_once __DIR__ . '/../../src/Services/DocumentService.php';
        }
    }

    public function testIsAllowedExtensionAcceptsValidExtension(): void
    {
        $this->assertTrue(DocumentService::isAllowedExtension('resume.pdf', ['pdf', 'docx']));
    }

    public function testIsAllowedExtensionIsCaseInsensitive(): void
    {
        $this->assertTrue(DocumentService::isAllowedExtension('resume.PDF', ['pdf', 'docx']));
    }

    public function testIsAllowedExtensionRejectsDisallowedExtension(): void
    {
        $this->assertFalse(DocumentService::isAllowedExtension('malware.php', ['pdf', 'docx']));
    }

    public function testIsAllowedExtensionRejectsEmptyFilename(): void
    {
        $this->assertFalse(DocumentService::isAllowedExtension('', ['pdf']));
    }

    public function testBuildStoredFilenameContainsPrefix(): void
    {
        $result = DocumentService::buildStoredFilename('resume', 'pdf');
        $this->assertStringContainsString('resume', $result);
        $this->assertStringContainsString('.pdf', $result);
    }

    public function testBuildStoredFilenameSanitizesPrefix(): void
    {
        $result = DocumentService::buildStoredFilename('../../../evil', 'pdf');
        $this->assertStringNotContainsString('..', $result);
        $this->assertStringNotContainsString('/', $result);
    }

    public function testBuildStoredFilenameIsUnique(): void
    {
        $name1 = DocumentService::buildStoredFilename('doc', 'pdf');
        $name2 = DocumentService::buildStoredFilename('doc', 'pdf');
        $this->assertNotSame($name1, $name2);
    }
}
