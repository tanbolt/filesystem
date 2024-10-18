<?php
namespace Tanbolt\Filesystem\Driver;

use Tanbolt\Filesystem\DriverInterface;

class AliYun implements DriverInterface
{
    /**
     * @inheritDoc
     */
    public function configure(array $config = [])
    {
        // TODO: Implement configure() method.
    }

    /**
     * @inheritDoc
     */
    public function has(string $filename)
    {
        // TODO: Implement has() method.
    }

    /**
     * @inheritDoc
     */
    public function getFiletype(string $path)
    {
        // TODO: Implement getFiletype() method.
    }

    /**
     * @inheritDoc
     */
    public function getLastModified(string $filename)
    {
        // TODO: Implement getLastModified() method.
    }

    /**
     * @inheritDoc
     */
    public function getSize(string $filename)
    {
        // TODO: Implement getSize() method.
    }

    /**
     * @inheritDoc
     */
    public function getMimeType(string $filename)
    {
        // TODO: Implement getMimeType() method.
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(string $filename)
    {
        // TODO: Implement getMetadata() method.
    }

    /**
     * @inheritDoc
     */
    public function getHash(string $filename)
    {
        // TODO: Implement getHash() method.
    }

    /**
     * @inheritDoc
     */
    public function getAcl(string $filename)
    {
        // TODO: Implement getAcl() method.
    }

    /**
     * @inheritDoc
     */
    public function setAcl(string $filename, int $acl)
    {
        // TODO: Implement setAcl() method.
    }

    /**
     * @inheritDoc
     */
    public function getContent(string $filename, bool $lock = false)
    {
        // TODO: Implement getContent() method.
    }

    /**
     * @inheritDoc
     */
    public function getStream(string $filename)
    {
        // TODO: Implement getStream() method.
    }

    /**
     * @inheritDoc
     */
    public function put(string $filename, $data, bool $lock = false)
    {
        // TODO: Implement put() method.
    }

    /**
     * @inheritDoc
     */
    public function append(string $filename, $data, bool $lock = false)
    {
        // TODO: Implement append() method.
    }

    /**
     * @inheritDoc
     */
    public function prepend(string $filename, $data, bool $lock = false)
    {
        // TODO: Implement prepend() method.
    }

    /**
     * @inheritDoc
     */
    public function rename(string $from, string $to, bool $overwrite = true)
    {
        // TODO: Implement rename() method.
    }

    /**
     * @inheritDoc
     */
    public function copy(string $from, string $to, bool $overwrite = true)
    {
        // TODO: Implement copy() method.
    }

    /**
     * @inheritDoc
     */
    public function unlink(string $filename)
    {
        // TODO: Implement unlink() method.
    }

    /**
     * @inheritDoc
     */
    public function mkdir(string $pathname, bool $recursive = true)
    {
        // TODO: Implement mkdir() method.
    }

    /**
     * @inheritDoc
     */
    public function mvdir(string $from, string $to, bool $overwrite = true)
    {
        // TODO: Implement mvdir() method.
    }

    /**
     * @inheritDoc
     */
    public function cpdir(string $from, string $to, bool $overwrite = true)
    {
        // TODO: Implement cpdir() method.
    }

    /**
     * @inheritDoc
     */
    public function cleandir(string $pathname, bool $includeSelf = false)
    {
        // TODO: Implement cleandir() method.
    }

    /**
     * @inheritDoc
     */
    public function rmdir(string $pathname, bool $recursive = false)
    {
        // TODO: Implement rmdir() method.
    }

    /**
     * @inheritDoc
     */
    public function lists(string $dir, bool $expand = false, string $marker = null, int $max = 100)
    {
        // TODO: Implement lists() method.
    }
}
