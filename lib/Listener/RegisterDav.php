<?php
declare(strict_types=1);
namespace OCA\UploadSizeNotice\Listener;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\DAV\Events\SabrePluginAddEvent;
use OCA\UploadSizeNotice\Dav\UploadPlugin;
class RegisterDav implements IEventListener {
    public function __construct(private UploadPlugin $plugin) {}
    public function handle(Event $event): void {
        if ($event instanceof SabrePluginAddEvent || $event instanceof \OCP\SabrePluginEvent) {
            $server = $event->getServer();
            if ($server !== null && !$server->getPlugin(UploadPlugin::class)) { $server->addPlugin($this->plugin); }
        }
    }
}
