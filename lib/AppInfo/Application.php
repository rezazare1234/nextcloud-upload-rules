<?php
declare(strict_types=1);
namespace OCA\UploadSizeNotice\AppInfo;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCA\DAV\Events\SabrePluginAddEvent;
use OCA\UploadSizeNotice\Listener\LoadNotice;
use OCA\UploadSizeNotice\Listener\RegisterDav;
class Application extends App implements IBootstrap {
    public const APP_ID = 'upload_size_notice';
    public function __construct(array $params = []) { parent::__construct(self::APP_ID, $params); }
    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(BeforeTemplateRenderedEvent::class, LoadNotice::class);
        $context->registerEventListener(SabrePluginAddEvent::class, RegisterDav::class);
        $context->registerEventListener(\OCP\SabrePluginEvent::class, RegisterDav::class);
        $context->registerEventListener(\OCP\BeforeSabrePubliclyLoadedEvent::class, RegisterDav::class);
    }
    public function boot(IBootContext $context): void {}
}
