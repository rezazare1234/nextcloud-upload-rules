<?php
declare(strict_types=1);
namespace OCA\UploadSizeNotice\Settings;
use OCP\Settings\IIconSection;
use OCP\IURLGenerator;
class Section implements IIconSection {
    public function __construct(private IURLGenerator $urls) {}
    public function getID(): string { return 'upload_size_notice'; }
    public function getName(): string { return 'قوانین آپلود'; }
    public function getPriority(): int { return 65; }
    public function getIcon(): string { return $this->urls->imagePath('upload_size_notice', 'app.svg'); }
}
