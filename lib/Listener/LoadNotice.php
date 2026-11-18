<?php
declare(strict_types=1);
namespace OCA\UploadSizeNotice\Listener;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Services\IInitialState;
use OCP\IURLGenerator;
use OCP\Util;
use OCA\UploadSizeNotice\Service\PolicyStore;
class LoadNotice implements IEventListener {
    public function __construct(private IInitialState $state, private IURLGenerator $urls, private PolicyStore $store) {}
    public function handle(Event $event): void {
        if (!$event instanceof BeforeTemplateRenderedEvent || !in_array($event->getResponse()->getApp(), ['files', 'files_sharing'], true)) { return; }
        $public = $event->getResponse()->getApp() === 'files_sharing';
        $this->state->provideInitialState('config', ['policy' => $this->store->effective($public), 'url' => $this->urls->linkToRoute('upload_size_notice.config.policy', $public ? ['public'=>true] : [])]);
        Util::addScript('upload_size_notice', 'policy');
        Util::addScript('upload_size_notice', 'notice-v3');
        Util::addStyle('upload_size_notice', 'notice');
    }
}
