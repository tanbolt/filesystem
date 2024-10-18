<?php
namespace Tanbolt\Filesystem;

interface AccessInterface
{
    const ACL_DEFAULT = 0;  // 继承上级
    const ACL_PRIVATE = 1;  // 私有读写
    const ACL_READ = 2;    // 公共读, 私有写
    const ACL_WRITE = 3;   // 公共读写

    // lists 返回数组的两个键值
    const TRUNCATED = 'truncated';
    const CONTENTS = 'contents';

    /**
     * 判断文件(夹)是否存在
     * @param string $filename
     * @return bool
     */
    public function has(string $filename);

    /**
     * 获取文件(夹)类型 (返回 fifo, char, dir, block, link, file, socket, unknown)
     * @param string $path
     * @return string|false
     */
    public function getFiletype(string $path);

    /**
     * 获取文件(夹)最后修改时间
     * @param string $filename
     * @return int|false
     */
    public function getLastModified(string $filename);

    /**
     * 获取文件大小
     * @param string $filename
     * @return int|false
     */
    public function getSize(string $filename);

    /**
     * 获取文件 mimeType
     * @param string $filename
     * @return ?string
     */
    public function getMimeType(string $filename);

    /**
     * 一次性获取文件(夹)基本信息 (type, path, size, lastModified, mimeType)
     * @param string $filename
     * @return array
     */
    public function getMetadata(string $filename);

    /**
     * 返回 file 的摘要 hash 值
     * @param string $filename
     * @return string
     */
    public function getHash(string $filename);

    /**
     * 获取文件的权限设置
     * @param string $filename
     * @return ?int
     */
    public function getAcl(string $filename);

    /**
     * 修改文件的权限设置
     * @param string $filename
     * @param int $acl
     * @return bool
     */
    public function setAcl(string $filename, int $acl);

    /**
     * 获取文件内容
     * @param string $filename 文件名
     * @param bool $lock 是否加锁
     * @return ?string
     */
    public function getContent(string $filename, bool $lock = false);

    /**
     * 获取文件 stream resource
     * @param string $filename
     * @return resource|false
     */
    public function getStream(string $filename);

    /**
     * 重写文件内容
     * @param string $filename 文件名
     * @param mixed|resource $data 文件内容
     * @param bool $lock 是否加锁
     * @return int|false
     */
    public function put(string $filename, $data, bool $lock = false);

    /**
     * 追加内容到指定文件
     * @param string $filename 文件名
     * @param mixed|resource $data 文件内容
     * @param bool $lock 是否加锁
     * @return int|false
     */
    public function append(string $filename, $data, bool $lock = false);

    /**
     * 在文件开头插入内容
     * @param string $filename 文件名
     * @param mixed|resource $data 文件内容
     * @param bool $lock 是否加锁
     * @return int|false
     */
    public function prepend(string $filename, $data, bool $lock = false);

    /**
     * 修改文件名
     * @param string $from 原文件名
     * @param string $to 新文件名
     * @param bool $overwrite 新文件名已存在,是否覆盖
     * @return bool
     */
    public function rename(string $from, string $to, bool $overwrite = true);

    /**
     * 复制文件
     * @param string $from 源文件路径
     * @param string $to 新文件路径
     * @param bool $overwrite 新文件路径已存在,是否覆盖
     * @return bool
     */
    public function copy(string $from, string $to, bool $overwrite = true);

    /**
     * 删除文件, 若文件不存在, 返回 true
     * @param string $filename
     * @return bool
     */
    public function unlink(string $filename);

    /**
     * 创建文件夹
     * @param string $pathname 文件夹路径
     * @param bool $recursive 是否递归
     * @return bool
     */
    public function mkdir(string $pathname, bool $recursive = true);

    /**
     * 移动文件夹
     * @param string $from 源文件夹路径
     * @param string $to 新文件夹路径
     * @param bool $overwrite 已存在,是否覆盖; 否则保留在原文件夹内
     * @return bool
     */
    public function mvdir(string $from, string $to, bool $overwrite = true);

    /**
     * 复制文件夹
     * @param string $from 源文件夹路径
     * @param string $to 新文件夹路径
     * @param bool $overwrite 新文件路径已存在,是否覆盖
     * @return bool
     */
    public function cpdir(string $from, string $to, bool $overwrite = true);

    /**
     * 删除所有空子文件夹, 若文件夹不存在, 返回 true
     * @param string $pathname   要清空的文件夹路径
     * @param bool $includeSelf  是否包含当前文件夹
     * @return bool
     */
    public function cleandir(string $pathname, bool $includeSelf = false);

    /**
     * 删除文件夹, 若文件夹不存在, 返回 true
     * @param string $pathname 文件夹路径
     * @param bool $recursive 是否递归
     * @return bool
     */
    public function rmdir(string $pathname, bool $recursive = false);

    /**
     * 获取文件(夹)列表
     * @param string $dir 前缀限制
     * @param bool $expand 是否展开子文件夹,展开会返回子文件夹的所有嵌套(包括文件夹本事)
     * @param ?string $marker 起始文件名, 对于超长列表, 可分页展示
     * @param int $max 返回文件最大数目
     * @return array|false
     */
    public function lists(string $dir, bool $expand = false, string $marker = null, int $max = 100);
}
