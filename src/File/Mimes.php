<?php

namespace Addons\Core\File;

use Symfony\Component\Mime\MimeTypes;

final class Mimes {

    private $mimes;
     /**
     * The singleton instance.
     *
     * @var ExtensionGuesser
     */
    private static $instance = null;

    public function __construct() {
        $this->mimes = new MimeTypes(config('mimes'));
    }

    /**
     * Returns the singleton instance.
     *
     * @return ExtensionGuesser
     */
    public static function getInstance(): self {
        return self::$instance ??= new self();
    }

    public function getMimeType(string $ext) {
        $mimes = $this->getMimeTypes($ext);
        return !empty($mimes) ? $mimes[0] : null;
    }

    public function getMimeTypes(string $ext) {
        return $this->mimes->getMimeTypes($ext);
    }
}
