<?php
declare(strict_types=1);
namespace OCA\UploadSizeNotice\Controller;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\IRequest;
use OCA\UploadSizeNotice\Service\PolicyStore;
class ConfigController extends Controller {
    public function __construct(string $appName, IRequest $request, private PolicyStore $store, private \OCP\IUserManager $users, private \OCP\IGroupManager $groups) { parent::__construct($appName, $request); }
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[PublicPage]
    public function policy(bool $public = false): JSONResponse {
        return new JSONResponse($this->store->effective($public), 200, ['Cache-Control' => 'no-store']);
    }
    public function directory(string $kind = 'users', string $search = '', int $offset = 0, array $ids = []): JSONResponse {
        if (!in_array($kind, ['users', 'groups'], true) || mb_strlen($search) > 256 || $offset < 0 || $offset > 100000 || count($ids) > 100) {
            return new JSONResponse(['message'=>'جست‌وجو معتبر نیست.'], 400);
        }
        $manager = $kind === 'users' ? $this->users : $this->groups;
        if ($ids) {
            $items = [];
            foreach ($ids as $id) {
                if (!is_string($id) || strlen($id) > 256) { return new JSONResponse(['message'=>'شناسه معتبر نیست.'], 400); }
                $item = $manager->get($id);
                $items[] = ['id'=>$id, 'label'=>$item?->getDisplayName() ?? $id, 'missing'=>$item === null];
            }
            return new JSONResponse(['items'=>$items, 'more'=>false], 200, ['Cache-Control'=>'no-store']);
        }
        $found = $kind === 'users' ? $this->users->searchDisplayName($search, 26, $offset) : $this->groups->search($search, 26, $offset);
        $items = [];
        foreach (array_slice(array_values($found), 0, 25) as $item) {
            $items[] = ['id'=>$kind === 'users' ? $item->getUID() : $item->getGID(), 'label'=>$item->getDisplayName()];
        }
        return new JSONResponse(['items'=>$items, 'more'=>count($found)>25], 200, ['Cache-Control'=>'no-store']);
    }
    // Default Nextcloud middleware requires administrator authentication AND CSRF.
    public function save(array $policy): JSONResponse {
        try { return new JSONResponse($this->store->save($policy), 200, ['Cache-Control' => 'no-store']); }
        catch (\InvalidArgumentException $e) { return new JSONResponse(['message' => $e->getMessage()], 400); }
    }
}
