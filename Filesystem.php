<?php
namespace Tanbolt\Filesystem;

use Tanbolt\Filesystem\Exception\ArgumentException;
use Tanbolt\Filesystem\Exception\FileNotExistException;
use Tanbolt\Filesystem\Exception\InvalidDriverException;

/**
 * Class Filesystem: 文件管理系统
 * @package Tanbolt\Filesystem
 */
class Filesystem implements FilesystemInterface
{
    /**
     * 附件 url 绑定的 域名
     * @var string
     */
    private $domain;

    /**
     * 接口驱动器
     * @var DriverInterface
     */
    private $driver;

    /**
     * 文件读写监听函数
     * @var callable|null
     */
    private $listener;

    /**
     * 创建 Filesystem 对象
     * @param ?string $domain
     * @param DriverInterface|string|null $driver
     * @param array $config
     */
    public function __construct(string $domain = null, $driver = null, array $config = [])
    {
        if ($domain) {
            $this->setDomain($domain);
        }
        if ($driver) {
            $this->setDriver($driver, $config);
        }
    }

    /**
     * 创建一个新的 filesystem 对象, 不设置参数则使用当前对象的配置
     * @param ?string $domain
     * @param DriverInterface|string|null $driver
     * @param array $config
     * @return static
     */
    public function instance(string $domain = null, $driver = null, array $config = [])
    {
        $domain = $domain ?: $this->domain;
        if (!$driver) {
            $driver = $this->driver;
        }
        return new static($domain, $driver, $config);
    }

    /**
     * @inheritDoc
     */
    public function setDomain(?string $domain)
    {
        $this->domain = $domain ? rtrim($domain, '/') : $domain;
        return $this;
    }

    /**
     * 获取附件绑定域名
     * @return ?string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @inheritDoc
     */
    public function setDriver($driver, array $config = [])
    {
        if ($driver instanceof DriverInterface) {
            $driver->configure($config);
            $this->driver = $driver;
        } elseif (is_string($driver)) {
            $this->driver = [$driver, $config];
        } else {
            throw new ArgumentException('driver must be string or DriverInterface instance.');
        }
        return $this;
    }

    /**
     * 获取文件管理系统的接口, 不设置参数则使用当前对象的属性
     * @param DriverInterface|string|null $driver
     * @param array $config
     * @return DriverInterface
     */
    public function getDriver($driver = null, array $config = [])
    {
        $useDefault = false;
        if (empty($driver)) {
            if ($this->driver instanceof DriverInterface) {
                return $this->driver;
            }
            if (is_array($this->driver) && !empty($this->driver[0])) {
                $useDefault = true;
                $driver = $this->driver[0];
                $config = $this->driver[1];
            } else {
                throw new InvalidDriverException('Filesystem adapter not configure');
            }
        }
        $interface = __NAMESPACE__.'\\DriverInterface';
        if (is_subclass_of($driver, $interface)) {
            $driver = new $driver();
        } elseif (is_subclass_of($driver = __NAMESPACE__.'\\Driver\\'.ucfirst($driver), $interface)) {
            $driver = new $driver();
        } else {
            throw new InvalidDriverException('Filesystem driver "'.$driver.'" not available');
        }
        if (!($driver instanceof DriverInterface)) {
            throw new InvalidDriverException('Filesystem driver instance failed');
        }
        $driver->configure($config);
        if ($useDefault) {
            $this->driver = $driver;
        }
        return $driver;
    }

    /**
     * @inheritDoc
     */
    public function setListener(callable $listener = null)
    {
        $this->listener = $listener;
        return $this;
    }

    /**
     * 获取文件操作回调函数
     * @return ?callable
     */
    public function getListener()
    {
        return $this->listener;
    }

    /**
     * 执行文件操作回调函数
     * @param string $event
     * @param mixed $data
     */
    public function callListener(string $event, $data)
    {
        if ($this->listener) {
            call_user_func($this->listener, $event, $data);
        }
    }

    /**
     * @inheritDoc
     */
    public function has(string $filename)
    {
        return $this->hasFile($filename);
    }

    /**
     * @inheritDoc
     */
    public function getUrl(string $filename)
    {
        return ($this->domain ?: '') . '/' . static::path($filename);
    }

    /**
     * @inheritDoc
     */
    public function getObject(string $filename)
    {
        $path = static::path($filename);
        if (!$this->hasFile($path, true)) {
            throw new FileNotExistException('File "'.$filename.'" not exist.');
        }
        return new File($this, $path);
    }

    /**
     * @param string $filename
     * @param false $normalized
     * @return bool
     */
    private function hasFile(string $filename, bool $normalized = false)
    {
        return $this->getDriver()->has($normalized ? $filename : static::path($filename));
    }

    /**
     * @inheritDoc
     */
    public function getFiletype(string $path)
    {
        return $this->getDriver()->getFiletype(static::path($path));
    }

    /**
     * @inheritDoc
     */
    public function getLastModified(string $filename)
    {
        return $this->getDriver()->getLastModified(static::path($filename));
    }

    /**
     * @inheritDoc
     */
    public function getSize(string $filename)
    {
        return $this->getDriver()->getSize(static::path($filename));
    }

    /**
     * @inheritDoc
     */
    public function getMimeType(string $filename)
    {
        return $this->getDriver()->getMimeType(static::path($filename));
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(string $filename)
    {
        return $this->getDriver()->getMetadata(static::path($filename));
    }

    /**
     * @inheritDoc
     */
    public function getHash(string $filename)
    {
        return $this->getDriver()->getHash(static::path($filename));
    }

    /**
     * @inheritDoc
     */
    public function getAcl(string $filename)
    {
        return $this->getDriver()->getAcl(static::path($filename));
    }

    /**
     * @inheritDoc
     */
    public function setAcl(string $filename, int $acl)
    {
        if ($this->getDriver()->setAcl($filename = static::path($filename), $acl)) {
            $this->callListener('acl', [$filename, $acl]);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getContent(string $filename, bool $lock = false)
    {
        return $this->getDriver()->getContent(static::path($filename), $lock);
    }

    /**
     * @inheritDoc
     */
    public function getStream(string $filename)
    {
        return $this->getDriver()->getStream(static::path($filename));
    }

    /**
     * @inheritDoc
     */
    public function put(string $filename, $data, bool $lock = false)
    {
        if ($this->getDriver()->put($filename = static::path($filename), $data, $lock)) {
            $this->callListener('put', $filename);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function append(string $filename, $data, bool $lock = false)
    {
        if ($this->getDriver()->append($filename = static::path($filename), $data, $lock)) {
            $this->callListener('put', $filename);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function prepend(string $filename, $data, bool $lock = false)
    {
        if ($this->getDriver()->prepend($filename = static::path($filename), $data, $lock)) {
            $this->callListener('put', $filename);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function rename(string $from, string $to, bool $overwrite = true)
    {
        if ($this->getDriver()->rename($from = static::path($from), $to = static::path($to), $overwrite)) {
            $this->callListener('rename', [$from, $to]);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function copy(string $from, string $to, bool $overwrite = true)
    {
        if ($this->getDriver()->copy($from = static::path($from), $to = static::path($to), $overwrite)) {
            $this->callListener('copy', [$from, $to]);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function unlink(string $filename)
    {
        if ($this->getDriver()->unlink($filename = static::path($filename))) {
            $this->callListener('unlink', $filename);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function mkdir(string $pathname, bool $recursive = true)
    {
        if ($this->getDriver()->mkdir($pathname = static::path($pathname), $recursive)) {
            $this->callListener('mkdir', $pathname);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function mvdir(string $from, string $to, bool $overwrite = true)
    {
        if ($this->getDriver()->mvdir($from = static::path($from), $to = static::path($to), $overwrite)) {
            $this->callListener('mvdir', [$from, $to, $overwrite]);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function cpdir(string $from, string $to, bool $overwrite = true)
    {
        if ($this->getDriver()->cpdir($from = static::path($from), $to = static::path($to), $overwrite)) {
            $this->callListener('cpdir', [$from, $to, $overwrite]);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function cleandir(string $pathname, bool $includeSelf = false)
    {
        if ($this->getDriver()->cleandir($pathname = static::path($pathname), $includeSelf)) {
            $this->callListener('cleandir', $pathname);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function rmdir(string $pathname, bool $recursive = false)
    {
        if ($this->getDriver()->rmdir($pathname = static::path($pathname), $recursive)) {
            $this->callListener('rmdir', $pathname);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function lists(string $dir, bool $expand = false, string $marker = null, int $max = 100)
    {
        return $this->getDriver()->lists(static::path($dir), $expand, $marker, $max);
    }

    /**
     * 去除路径中的第一个斜杠
     * @param string $pathname
     * @return string
     */
    private static function path(string $pathname)
    {
        return ltrim(Manager::normalizePath($pathname, '/'), '/');
    }
}
