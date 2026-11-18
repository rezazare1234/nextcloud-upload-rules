<?php
declare(strict_types=1);
namespace OCA\UploadSizeNotice\Dav;
class PolicyException extends \Sabre\DAV\Exception {
    public function __construct(string $message, private int $status) { parent::__construct($message); }
    public function getHTTPCode(): int { return $this->status; }
}
