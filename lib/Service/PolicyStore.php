<?php
declare(strict_types=1);
namespace OCA\UploadSizeNotice\Service;
use OCP\IAppConfig;
final class PolicyStore {
    public function __construct(private IAppConfig $config, private \OCP\IUserSession $session, private \OCP\IGroupManager $groups) {}
    public function effective(bool $public = false): array {
        $user = $public ? null : $this->session->getUser();
        return Policy::forActor($this->get(), $user?->getUID(), $user === null ? [] : $this->groups->getUserGroupIds($user));
    }
    public function get(): array {
        $json = $this->config->getValueString('upload_size_notice', 'policy', '');
        return $json === '' ? Policy::defaults() : Policy::validate(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }
    public function save(array $input): array {
        $policy = Policy::validate($input);
        $this->config->setValueString('upload_size_notice', 'policy', json_encode($policy, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $policy;
    }
}
