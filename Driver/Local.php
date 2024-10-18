<?php
namespace Tanbolt\Filesystem\Driver;

use Tanbolt\Filesystem\Manager;
use Tanbolt\Filesystem\DriverInterface;

class Local implements DriverInterface
{
    /**
     * @var string
     */
    private $root;

    /**
     * 获取文件真实路径
     * @param ?string $filename
     * @return string
     */
    public function path(string $filename = null)
    {
        return $this->root . ($filename ?: '');
    }

    /**
     * @inheritDoc
     */
    public function configure(array $config = [])
    {
        if (isset($config['root'])) {
            $this->root = rtrim(Manager::normalizePath($config['root']), '/') . '/';
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function has(string $filename)
    {
        return Manager::has($this->path($filename));
    }

    /**
     * @inheritDoc
     */
    public function getFiletype(string $path)
    {
        return Manager::filetype($this->path($path));
    }

    /**
     * @inheritDoc
     */
    public function getLastModified(string $filename)
    {
        return Manager::lastModified($this->path($filename));
    }

    /**
     * @inheritDoc
     */
    public function getSize(string $filename)
    {
        return Manager::size($this->path($filename));
    }

    /**
     * @inheritDoc
     */
    public function getMimeType(string $filename)
    {
        return Manager::mimeType($this->path($filename));
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(string $filename)
    {
        $metadata = Manager::metadata($this->path($filename));
        $metadata['path'] = substr($metadata['path'], strlen($this->root));
        return $metadata;
    }

    /**
     * @inheritDoc
     */
    public function getHash(string $filename)
    {
        return md5_file($this->path($filename));
    }

    /**
     * @inheritDoc
     */
    public function getAcl(string $filename)
    {
        return Manager::acl($this->path($filename));
    }

    /**
     * @inheritDoc
     */
    public function setAcl(string $filename, int $acl)
    {
        return Manager::acl($this->path($filename), $acl);
    }

    /**
     * @inheritDoc
     */
    public function getContent(string $filename, bool $lock = false)
    {
        return Manager::content($this->path($filename), $lock);
    }

    /**
     * @inheritDoc
     */
    public function getStream(string $filename)
    {
        return Manager::stream($this->path($filename));
    }

    /**
     * @inheritDoc
     */
    public function put(string $filename, $data, bool $lock = false)
    {
        return Manager::put($this->path($filename), $data, $lock);
    }

    /**
     * @inheritDoc
     */
    public function append(string $filename, $data, bool $lock = false)
    {
        return Manager::append($this->path($filename), $data, $lock);
    }

    /**
     * @inheritDoc
     */
    public function prepend(string $filename, $data, bool $lock = false)
    {
        return Manager::prepend($this->path($filename), $data, $lock);
    }

    /**
     * @inheritDoc
     */
    public function rename(string $from, string $to, bool $overwrite = true)
    {
        return Manager::rename($this->path($from), $this->path($to), $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function copy(string $from, string $to, bool $overwrite = true)
    {
        return Manager::copy($this->path($from), $this->path($to), $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function unlink(string $filename)
    {
        return Manager::unlink($this->path($filename));
    }

    /**
     * @inheritDoc
     */
    public function mkdir(string $pathname, bool $recursive = true)
    {
        return Manager::mkdir($this->path($pathname), $recursive);
    }

    /**
     * @inheritDoc
     */
    public function mvdir(string $from, string $to, bool $overwrite = true)
    {
        return Manager::mvdir($this->path($from), $this->path($to), $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function cpdir(string $from, string $to, bool $overwrite = true)
    {
        return Manager::cpdir($this->path($from), $this->path($to), $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function cleandir(string $pathname, bool $includeSelf = false)
    {
        return Manager::cleandir($this->path($pathname), $includeSelf);
    }

    /**
     * @inheritDoc
     */
    public function rmdir(string $pathname, bool $recursive = false)
    {
        return Manager::rmdir($this->path($pathname), $recursive);
    }

    /**
     * @inheritDoc
     */
    public function lists(string $dir, bool $expand = false, string $marker = null, int $max = 100)
    {
        $len = strlen($this->root);
        $lists = Manager::lists($this->root.$dir, $expand, $marker, $max);
        $lists[static::CONTENTS] = array_map(function ($file) use ($len) {
            return substr($file, $len);
        }, $lists[static::CONTENTS]);
        return $lists;
    }
}
