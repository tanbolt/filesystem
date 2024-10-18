<?php
namespace Tanbolt\Filesystem;

use InvalidArgumentException;

/**
 * Class File: 文件 OOP 对象
 * @package Tanbolt\Filesystem
 * @property-read string $url 访问地址
 * @property-read string $path 文件地址
 * @property-read string $dirname 文件所在文件夹路径
 * @property-read string $basename 文件名(包括文件后缀)
 * @property-read string $filename 文件名(不包括文件后缀)
 * @property-read string $extension 文件名后缀
 * @property-read string|false $filetype 类型(file|dir)
 * @property-read int|false $size 文件大小
 * @property-read int|false $lastModified 文件最后修改时间
 * @property-read ?string $mimeType 文件 mimeType
 * @property-read array $metadata 文件头信息
 * @property-read ?string $hash 文件 hash 摘要
 * @property-read ?int $acl 文件权限
 * @property-read ?string $content 内容(不加锁获取)
 * @property-read ?string $lockContent 内容(加锁获取)
 * @property-read resource|false $stream 文件打开的指针
 * @property-read FilesystemInterface $filesystem 文件使用的 Filesystem
 */
class File
{
    /**
     * 文件绑定的管理器
     * @var FilesystemInterface
     */
    private $fs;

    /**
     * 文件名
     * @var string
     */
    private $filePath;

    /**
     * 文件指针缓存
     * @var resource
     */
    private $fileStream;

    /**
     * 文件对象属性
     * @var array
     */
    private static $properties = [
        'content', 'lockContent', 'stream',
        'dirname', 'basename', 'filename', 'extension', 'url',
        'filetype', 'size', 'lastModified', 'mimeType', 'metadata', 'acl', 'hash',
    ];

    /**
     * File constructor.
     * @param FilesystemInterface $filesystem
     * @param string $filepath
     */
    public function __construct(FilesystemInterface $filesystem, string $filepath)
    {
        $this->fs = $filesystem;
        $this->filePath = $filepath;
    }

    /**
     * 修改文件的权限设置
     * @param int $acl
     * @return bool
     */
    public function setAcl(int $acl)
    {
        return $this->fs->setAcl($this->filePath, $acl);
    }

    /**
     * 更改文件内容
     * @param mixed|resource $data 文件内容
     * @param bool $lock 是否加锁
     * @return int|false
     */
    public function put($data, bool $lock = false)
    {
        return $this->fs->put($this->filePath, $data, $lock);
    }

    /**
     * 追加内容到指定文件
     * @param mixed|resource $data 文件内容
     * @param bool $lock 是否加锁
     * @return int|false
     */
    public function append($data, bool $lock = false)
    {
        return $this->fs->append($this->filePath, $data, $lock);
    }

    /**
     * 在文件开头插入内容
     * @param mixed|resource $data 文件内容
     * @param bool $lock 是否加锁
     * @return int|false
     */
    public function prepend($data, bool $lock = false)
    {
        return $this->fs->prepend($this->filePath, $data, $lock);
    }

    /**
     * 修改文件名
     * @param string $newName 新文件名
     * @param bool $overwrite 新文件名已存在,是否覆盖
     * @return bool
     */
    public function rename(string $newName, bool $overwrite = true)
    {
        if ($this->fs->rename($this->filePath, $newName, $overwrite)) {
            $this->filePath = ltrim(Manager::normalizePath($newName, '/'), '/');
            return true;
        }
        return false;
    }

    /**
     * 复制文件, 复制失败:返回 false;  复制成功: 返回新文件的 File 对象
     * @param string $toName 新文件名
     * @param bool $overwrite 新文件名已存在,是否覆盖
     * @return File|false
     */
    public function copy(string $toName, bool $overwrite = true)
    {
        return $this->fs->copy($this->filePath, $toName, $overwrite) ? $this->fs->getObject($toName) : false;
    }

    /**
     * 删除文件
     * @return bool
     */
    public function unlink()
    {
        return $this->fs->unlink($this->filePath);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return in_array($name, static::$properties);
    }

    /**
     * @param $name
     * @return FilesystemInterface|bool|int|resource|string|null
     */
    public function __get($name)
    {
        switch ($name) {
            case 'filesystem':
                return $this->fs;
            case 'path':
                return $this->filePath;
            case 'lockContent':
                return $this->fs->getContent($this->filePath, true);
            case 'stream':
                if (!is_resource($this->fileStream)) {
                    $this->fileStream = $this->fs->getStream($this->filePath);
                }
                return $this->fileStream;
            case 'dirname':
            case 'basename':
            case 'filename':
            case 'extension':
                return Manager::{$name}($this->filePath);
            case 'url':
                return $this->fs->getUrl($this->filePath);
        }
        if (!in_array($name, static::$properties)) {
            throw new InvalidArgumentException('Undefined property: '.__CLASS__.'::$'.$name);
        }
        $method = 'get'.ucfirst($name);
        return $this->fs->{$method}($this->filePath);
    }
}
