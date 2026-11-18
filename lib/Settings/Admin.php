<?php
declare(strict_types=1);
namespace OCA\UploadSizeNotice\Settings;
use OCP\Settings\ISettings;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Util;
use OCP\IURLGenerator;
use OCA\UploadSizeNotice\Service\PolicyStore;
class Admin implements ISettings {
    public function __construct(private PolicyStore $store, private IURLGenerator $urls) {}
    public function getForm(): TemplateResponse {
        Util::addScript('upload_size_notice', 'policy');
        Util::addScript('upload_size_notice', 'admin');
        Util::addStyle('upload_size_notice', 'admin');
        return new TemplateResponse('upload_size_notice', 'admin', ['policy' => $this->store->get(), 'saveUrl' => $this->urls->linkToRoute('upload_size_notice.config.save'), 'directoryUrl' => $this->urls->linkToRoute('upload_size_notice.config.directory')]);
    }
    public function getSection(): string { return 'upload_size_notice'; }
    public function getPriority(): int { return 10; }
}
