<?php
declare(strict_types=1);
namespace OCA\UploadSizeNotice\Dav;
use OCA\UploadSizeNotice\Service\Policy;
use OCA\UploadSizeNotice\Service\PolicyStore;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Upload\FutureFile;
use OCA\DAV\Upload\UploadFile;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\HTTP\RequestInterface;

/** Upload-only checks: ordinary existing-file moves, copies and downloads are untouched. */
class UploadPlugin extends ServerPlugin {
    private Server $server;
    private ?array $policy = null;
    private array $checked = [];
    public function __construct(private PolicyStore $store) {}
    public function initialize(Server $server): void {
        $this->server = $server;
        // Auth runs first. Run before NC's chunk V2 PUT and MOVE handlers (100/90).
        $server->on('beforeMethod:PUT', $this->beforePut(...), 80);
        $server->on('beforeMethod:MKCOL', $this->beforeMkcol(...), 80);
        $server->on('beforeMove', $this->beforeMove(...), 80);
        // Legacy webdav loads some plugins after beforeMethod has started.
        $server->on('beforeCreateFile', function ($path, &$data, $parent): void {
            if ($parent instanceof Directory) { $this->guardData($path, $data); }
        }, 80);
        $server->on('beforeWriteContent', function ($path, $node, &$data): void {
            if ($node instanceof \OCA\DAV\Connector\Sabre\File) { $this->guardData($path, $data); }
        }, 80);
    }
    private function policy(): array {
        $uri = $this->server->httpRequest->getUrl();
        return $this->policy ??= $this->store->effective(str_contains($uri, '/public.php/') || str_contains($uri, '/dav/public-files/'));
    }
    private function check(string $path, int|float $size): void {
        $violation = Policy::violation($this->policy(), basename($path), $size);
        if ($violation !== null) {
            $this->server->httpResponse->setHeader('X-Upload-Rule', 'upload_size_notice');
            throw new PolicyException($violation['message'], $violation['status']);
        }
    }
    private function isChunk(string $path): bool { return preg_match('#^uploads/[^/]+/[^/]+(?:/|$)#', ltrim($path, '/')) === 1; }
    private function destination(RequestInterface $request): ?string {
        $header = $request->getHeader('Destination');
        return $header === null || $header === '' ? null : $this->server->calculateUri($header);
    }
    private function length(?string $header): ?int {
        if ($header === null || $header === '') { return null; }
        if (!preg_match('/^[0-9]+$/D', $header) || strlen(ltrim($header, '0')) > 16) {
            throw new BadRequest('Invalid upload length');
        }
        return (int)$header;
    }
    public function beforeMkcol(RequestInterface $request): void {
        if (!$this->policy()['enabled'] || !$this->isChunk($request->getPath())) { return; }
        $destination = $this->destination($request);
        if ($destination !== null) { $this->check($destination, $this->length($request->getHeader('OC-Total-Length')) ?? 0); }
    }
    public function beforePut(RequestInterface $request): void {
        if (!$this->policy()['enabled']) { return; }
        $path = $request->getPath();
        if ($this->isChunk($path)) {
            $destination = $this->destination($request);
            if ($destination !== null) {
                // Early rejection; final MOVE independently checks the stored size.
                $this->check($destination, $this->length($request->getHeader('OC-Total-Length')) ?? 0);
            }
            return;
        }
        // Avoid applying a files policy to calendars, address books and other DAV collections.
        $parentPath = dirname($path);
        if ($parentPath === '.') { $parentPath = ''; }
        try { $parent = $this->server->tree->getNodeForPath($parentPath); }
        catch (NotFound) { return; }
        if (!$parent instanceof Directory) { return; }
        $length = $this->length($request->getHeader('Content-Length'));
        $this->check($path, $length ?? 0);
        $this->checked[$path] = true;
        if ($length !== null) { return; }
        $request->setBody($this->buffer($path, $request->getBodyAsStream()));
    }
    private function guardData(string $path, mixed &$data): void {
        if (!$this->policy()['enabled'] || isset($this->checked[$path]) || $this->isChunk($path)) { return; }
        $length = $this->length($this->server->httpRequest->getHeader('Content-Length'));
        if (is_string($data)) { $length = strlen($data); }
        $this->check($path, $length ?? 0);
        if ($length === null && is_resource($data)) { $data = $this->buffer($path, $data); }
        $this->checked[$path] = true;
    }
    private function buffer(string $path, mixed $input): mixed {
        // HTTP transfer-encoding uploads have no known length. Buffer at most limit+1
        // before touching the target, then let Sabre consume the identical bytes.
        $rule = Policy::rule($this->policy(), basename($path));
        if ($rule === null) { return $input; }
        $limit = $rule['maxBytes'];
        $buffer = fopen('php://temp/maxmemory:2097152', 'w+b');
        if ($buffer === false) { throw new \RuntimeException('Cannot allocate upload buffer'); }
        $count = 0;
        try {
            while (!feof($input)) {
                $part = fread($input, (int)min(1048576, $limit + 1 - $count));
                if ($part === false || ($part === '' && !feof($input))) { throw new BadRequest('Cannot read upload stream'); }
                $count += strlen($part);
                $this->check($path, $count);
                if (fwrite($buffer, $part) !== strlen($part)) { throw new \RuntimeException('Cannot buffer upload stream'); }
            }
            rewind($buffer);
            return $buffer;
        } catch (\Throwable $error) {
            fclose($buffer);
            throw $error;
        }
    }
    public function beforeMove(string $sourcePath, string $destination): void {
        if (!$this->policy()['enabled'] || !$this->isChunk($sourcePath)) { return; }
        $node = $this->server->tree->getNodeForPath($sourcePath);
        if ($node instanceof FutureFile || $node instanceof UploadFile) {
            $this->check($destination, $node->getSize());
        }
    }
}
