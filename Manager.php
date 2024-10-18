<?php
namespace Tanbolt\Filesystem;

use Throwable;
use Tanbolt\Mime\Magic;

/**
 * Class Manager: 本地文件操作工具箱
 * @package Tanbolt\Filesystem
 */
class Manager
{
    /**
     * 将 Path 格式化为标准绝对路径
     * @param string $path 文件路径
     * @param ?string $forceDash 强制使用指定的连接符, 缺省: win 是反斜杠(\), linux 是斜杠(/)
     * @return string
     */
    public static function normalizePath(string $path, string $forceDash = null)
    {
        // windows path
        if ($win = '\\' === DIRECTORY_SEPARATOR) {
            $path = str_replace('\\', '/', $path);
        }
        $path = preg_replace('/\/+/', '/', $path);
        $segments = explode('/',$path);
        $segments = array_reverse($segments);
        $path = [];
        $path_len = 0;
        while ($segments){
            $segment = array_pop($segments);
            switch ($segment) {
                case '.':
                    break;
                case '..':
                    if( !$path_len || ('..' === $path[$path_len-1]) ){
                        $path[] = $segment;
                        $path_len++;
                    } else {
                        array_pop($path);
                        $path_len--;
                    }
                    break;
                default:
                    $path[] = $segment;
                    $path_len++;
                    break;
            }
        }
        return implode($forceDash ?: ($win ? '\\' : '/'), $path);
    }

    /**
     * 由文件(夹)路径获取文件所在文件夹
     * @param string $filename
     * @return string
     */
    public static function dirname(string $filename)
    {
        return pathinfo($filename, PATHINFO_DIRNAME);
    }

    /**
     * 由文件(夹)路径获取文件名(包括文件后缀)
     * @param string $filename
     * @return string
     */
    public static function basename(string $filename)
    {
        return pathinfo($filename, PATHINFO_BASENAME);
    }

    /**
     * 由文件(夹)路径获取文件名(不包括文件后缀)
     * @param string $filename
     * @return string
     */
    public static function filename(string $filename)
    {
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * 由文件路径获取文件名后缀
     * @param string $filename
     * @return string
     */
    public static function extension(string $filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * 判断文件(夹)是否存在
     * @param string $filename
     * @return bool
     */
    public static function has(string $filename)
    {
        return file_exists($filename);
    }

    /**
     * 获取文件(夹)类型
     * @param string $path
     * @return string|false
     */
    public static function filetype(string $path)
    {
        return filetype($path);
    }

    /**
     * 获取文件(夹)最后修改时间
     * @param string $filename
     * @return int|false
     */
    public static function lastModified(string $filename)
    {
        return filemtime($filename);
    }

    /**
     * 获取文件大小
     * @param string $filename
     * @return int|false
     */
    public static function size(string $filename)
    {
        return filesize($filename);
    }

    /**
     * 由文件路径获取文件 mimeType
     * @param string $filename
     * @return ?string
     */
    public static function mimeType(string $filename)
    {
        if (function_exists('finfo_file')) {
            return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filename);
        }
        return Magic::guessMimeTypeByExtension(static::extension($filename));
    }

    /**
     * 一次性获取文件(夹)主要信息 (type, path, size, lastModified, mimeType)
     * @param string $path
     * @return array
     */
    public static function metadata(string $path)
    {
        $path = rtrim(static::normalizePath($path, '/'), '/');
        $type = static::filetype($path);
        $meta = [
            'type' => $type,
            'path' => $path . ('dir' === $type ? '/' : ''),
            'size' => 0,
            'lastModified' => static::lastModified($path),
            'mimeType' => ''
        ];
        if ('file' === $type) {
            $meta['size'] = static::size($path);
            $meta['mimeType'] = static::mimeType($path);
        }
        return $meta;
    }

    /**
     * 设置 / 获取 文件(夹)的权限：该函数在 win 系统不生效，linux 系统文件(夹) 要比该函数复杂很多，
     * > 考虑到针对资源型文件的常用设置，以及目前主流的云存储权限，抽象几个常用情况，
     * 如果需要做更复杂的权限读写，则建议直接使用 fileperms 和 chmod 函数
     * @param string $filename 文件路径
     * @param ?int $acl 要设置的文件权限
     * @return ?int|bool 获取: 返回 null 代表获取失败, 否则返回 int;  设置: 返回 bool
     */
    public static function acl(string $filename, int $acl = null)
    {
        return null === $acl ? static::getAcl($filename) : static::setAcl($filename, $acl);
    }

    /**
     * 获取文件(夹)的权限
     * @param string $filename
     * @return ?int
     */
    private static function getAcl(string $filename)
    {
        clearstatcache(false, $filename);
        $perms = @fileperms($filename);
        // 如果无法获取当前权限, 证明 owner 自己权限不足, 返回 null
        if (!$perms) {
            return null;
        }
        $dir = 0x4000 == ($perms & 0x4000);
        // 如果 owner 自己的读写权限都不全(若是文件夹,还必须有执行权限) 直接返回 null
        if (!($perms & 0x0100) || !($perms & 0x0080) || !(!$dir || ($perms & 0x0040))) {
            return null;
        }
        // 其他用户权限, 文件夹必须有执行权限
        if ($dir && !($perms & 0x0001)) {
            return AccessInterface::ACL_PRIVATE;
        }
        $read = $perms & 0x0004;
        $write = $perms & 0x0002;
        if ($read && $write) {
            return AccessInterface::ACL_WRITE;
        }
        if ($read) {
            return AccessInterface::ACL_READ;
        }
        return AccessInterface::ACL_PRIVATE;
    }

    /**
     * 设置文件(夹)的权限
     * @param string $filename
     * @param int $acl
     * @return bool
     */
    private static function setAcl(string $filename, int $acl)
    {
        $isDir = is_dir($filename);
        $chmod = static::getAclPerm($acl, $isDir);
        // ACL_DEFAULT 或 不合法的权限, 继承上级权限
        if (null === $chmod) {
            if (($acl = null === static::getAcl(dirname($filename))) ) {
                return false;
            }
            $chmod = static::getAclPerm($acl, $isDir);
        }
        if (false === chmod($filename, $chmod)) {
            return false;
        }
        return true;
    }

    /**
     * 由 ACL 值获取 unix 权限值
     * @param int $acl
     * @param bool $dir
     * @return ?int
     */
    private static function getAclPerm(int $acl, bool $dir)
    {
        switch ($acl) {
            case AccessInterface::ACL_WRITE:
                return $dir ? 0777 : 0666;
            case AccessInterface::ACL_READ:
                return $dir ? 0755 : 0644;
            case AccessInterface::ACL_PRIVATE:
                return $dir ? 0700 : 0600;
        }
        return null;
    }

    /**
     * 获取文件内容
     * @param string $filename 文件名
     * @param bool $lock 是否加锁
     * @return ?string
     */
    public static function content(string $filename, bool $lock = false)
    {
        if (!is_file($filename)) {
            return null;
        }
        if (!$lock) {
            return file_get_contents($filename);
        }
        $data = '';
        if ($fp = fopen($filename, 'rb')) {
            try {
                if (flock($fp, LOCK_SH)) {
                    clearstatcache(true, $filename);
                    $data = fread($fp, filesize($filename) ?: 1);
                    flock($fp, LOCK_UN);
                }
                @fclose($fp);
            } catch (Throwable $e) {
                @fclose($fp);
            }
        }
        return $data;
    }

    /**
     * 获取文件只读 stream resource
     * @param string $filename
     * @return resource|false
     */
    public static function stream(string $filename)
    {
        return fopen($filename, 'rb');
    }

    /**
     * 更改文件内容
     * @param string $filename 文件名
     * @param mixed|resource $data 文件内容, 任何可以 (string) 转换的变量 或 resource
     * @param bool $lock 是否加锁
     * @return int|false
     */
    public static function put(string $filename, $data, bool $lock = false)
    {
        if (!static::mkdir(dirname($filename))) {
            return false;
        }
        if (!is_resource($data)) {
            return file_put_contents($filename, (string) $data, $lock ? LOCK_EX : 0);
        }
        if (!($fd = fopen($filename, 'w+b'))) {
            return false;
        }
        if ($lock) {
            flock($fd, LOCK_EX);
        }
        $bit = stream_copy_to_stream($data, $fd);
        if ($lock) {
            flock($fd, LOCK_UN);
        }
        if (!fclose($fd)) {
            return false;
        }
        return $bit;
    }

    /**
     * 追加内容到指定文件
     * @param string $filename 文件名
     * @param mixed|resource $data 文件内容, 任何可以 (string) 转换的变量 或 resource
     * @param bool $lock
     * @return int|false
     */
    public static function append(string $filename, $data, bool $lock = false)
    {
        if (!static::mkdir(dirname($filename))) {
            return false;
        }
        if (!is_resource($data)) {
            return file_put_contents($filename, (string) $data, $lock ? LOCK_EX|FILE_APPEND : FILE_APPEND);
        }
        if (!($fd = fopen($filename, 'a+b'))) {
            return false;
        }
        if ($lock) {
            flock($fd, LOCK_EX);
        }
        $bit = 0;
        while (!feof($data)) {
            $bit += fwrite($fd, fread($data, 8192));
        }
        if ($lock) {
            flock($fd, LOCK_UN);
        }
        if (!fclose($fd)) {
            return false;
        }
        return $bit;
    }

    /**
     * 在文件开头插入内容
     * @param string $filename 文件名
     * @param mixed|resource $data 文件内容, 任何可以 (string) 转换的变量 或 resource
     * @param bool $lock
     * @return int|false
     */
    public static function prepend(string $filename, $data, bool $lock = false)
    {
        if (!static::mkdir(dirname($filename))) {
            return false;
        }
        if ($lock) {
            // $filename 可能不存在, 如果是加锁写入, 这里等同于创建一个空文件占位
            if(!($fd = fopen($filename, 'w+b')) ) {
                return false;
            }
            flock($fd, LOCK_EX);
        } else {
            // 若不加锁方式, 只读方式打开即可
            $fd = @fopen($filename, 'rb');
        }
        // 创建临时文件并转移数据
        $bit = false;
        if ($tmp = fopen($temp = $filename . '._tmp_', 'w+b')) {
            $bit = 0;
            if (is_resource($data)) {
                while (!feof($data)) {
                    $bit += fwrite($tmp, fread($data, 8192));
                }
            } else {
                $bit += fwrite($tmp, (string) $data);
            }
            if ($fd) {
                while (!feof($fd)) {
                    fwrite($tmp, fread($fd, 8192));
                }
            }
            fclose($tmp);
        }
        if ($fd) {
            if ($lock) {
                flock($fd, LOCK_UN);
            }
            fclose($fd);
        }
        // 临时文件写入失败,返回false,否则改名并返回写入数据长度
        if (false === $bit) {
            return false;
        }
        if (!rename($temp, $filename)) {
            unlink($temp);
            return false;
        }
        return $bit;
    }

    /**
     * 修改文件名
     * @param string $from 原文件名
     * @param string $to 新文件名
     * @param bool $overwrite 新文件名已存在,是否覆盖
     * @return bool
     */
    public static function rename(string $from, string $to, bool $overwrite = true)
    {
        if (!$overwrite && is_file($to)) {
            return false;
        }
        return static::moveFile($from, $to);
    }

    /**
     * 移动文件
     * @param string $from
     * @param string $to
     * @return bool
     */
    private static function moveFile(string $from, string $to)
    {
        return rename($from, $to);
    }

    /**
     * 复制文件
     * @param string $from 源文件
     * @param string $to 新文件名
     * @param bool $overwrite 新文件名已存在,是否覆盖
     * @return bool
     */
    public static function copy(string $from, string $to, bool $overwrite = true)
    {
        if (!$overwrite && is_file($to)) {
            return false;
        }
        return copy($from, $to);
    }

    /**
     * 删除文件, 若文件不存在, 返回 true
     * @param string $filename
     * @return bool
     */
    public static function unlink(string $filename)
    {
        return !is_file($filename) || unlink($filename);
    }

    /**
     * 创建文件夹
     * @param string $pathname 文件夹路径
     * @param bool $recursive 是否递归
     * @return bool
     */
    public static function mkdir(string $pathname, bool $recursive = true)
    {
        if (is_dir($pathname)) {
            return true;
        }
        $umask = umask(0);
        $make = mkdir($pathname, 0755, $recursive);
        umask($umask);
        return $make;
    }

    /**
     * 移动文件夹
     * @param string $from 源文件夹路径
     * @param string $to 新文件夹路径
     * @param bool $overwrite 已存在,是否覆盖; 否则保留在原文件夹内
     * @return bool
     */
    public static function mvdir(string $from, string $to, bool $overwrite = true)
    {
        $mv = static::transferDir($from, $to, $overwrite, true);
        if ($mv) {
            static::cleandir($from, true);
        }
        return $mv;
    }

    /**
     * 复制文件夹
     * @param string $from 源文件夹路径
     * @param string $to 新文件夹路径
     * @param bool $overwrite 新文件路径已存在,是否覆盖
     * @return bool
     */
    public static function cpdir(string $from, string $to, bool $overwrite = true)
    {
        return static::transferDir($from, $to, $overwrite);
    }

    /**
     * 移动/复制 文件夹
     * @param string $from
     * @param string $to
     * @param bool $overwrite
     * @param bool $move
     * @return bool
     */
    private static function transferDir(string $from, string $to, bool $overwrite = true, bool $move = false)
    {
        $from = rtrim(static::normalizePath($from, '/'), '/');
        if (!is_dir($from)) {
            return false;
        }
        $to = rtrim(static::normalizePath($to, '/'), '/');
        if ($from === $to) {
            return true;
        }
        // 目标文件夹不存在, 移动文件夹, 直接 rename 即可
        if (!is_dir($to) && $move) {
            return rename($from, $to);
        }
        // 合并到目标文件夹
        return static::transferRecursive($from, $to, $overwrite, $move);
    }

    /**
     * 合并 from 文件夹到 to 文件夹
     * @param string $from
     * @param string $to
     * @param bool $overwrite
     * @param bool $move
     * @return bool
     */
    private static function transferRecursive(string $from, string $to, bool $overwrite = true, bool $move = false)
    {
        if (!is_dir($to) && !mkdir($to, 0755)) {
            return false;
        }
        $test = true;
        static::glob($from, function($path) use ($to, $overwrite, $move, &$test) {
            $to .= '/'.basename($path);
            if ('/' === substr($path, -1)) {
                // 文件夹
                if ($move && !is_dir($to)) {
                    // 移动文件夹 + 目标文件夹不存在 -> 可直接 rename
                    $test = rename($path, $to);
                } else {
                    // 递归合并文件夹
                    $test = static::transferRecursive($path, $to, $overwrite, $move);
                }
            } else {
                // 文件
                if (!$overwrite && is_file($to)) {
                    // 不覆盖 + 文件已存在 -> 跳过
                    $test = true;
                } else {
                    $test = $move ? rename($path, $to) : copy($path, $to);

                }
            }
            return $test ? false : null;
        });
        return $test;
    }

    /**
     * 删除所有空子文件夹, 若文件夹不存在, 返回 true
     * @param string $pathname   要清空的文件夹路径
     * @param bool $includeSelf  是否包含当前文件夹
     * @return bool
     */
    public static function cleandir(string $pathname, bool $includeSelf = false)
    {
        if (!is_dir($pathname)) {
            return true;
        }
        $clean = static::cleandirRecursive(rtrim(Manager::normalizePath($pathname, '/'), '/').'/', true);
        if (false === $clean) {
            return false;
        }
        return !($includeSelf && true === $clean) || rmdir($pathname);
    }

    /**
     * 清除空目录(不包含目录本事)
     * @param string $pathname
     * @param bool $top
     * @return bool|null  false:失败,  true:成功且目录本事也为空了,  null:成功但目录内仍有文件
     */
    private static function cleandirRecursive(string $pathname, bool $top = false)
    {
        $lists = glob($pathname.'*');
        $count = count($lists);
        foreach ($lists as $file) {
            if (!is_dir($file)) {
                continue;
            }
            $clean = static::cleandirRecursive($file.'/');
            if (false === $clean) {
                // 删除文件夹失败
                return false;
            }
            if (true === $clean) {
                // 删除文件夹成功
                $count--;
            }
            // 隐含另外一种结果: 目录不为空
        }
        // 该目录下已无文件
        if (!$count) {
            return $top || rmdir($pathname);
        }
        // 该目录下无空目录, 但仍有文件
        return null;
    }

    /**
     * 删除文件夹, 若文件夹不存在, 返回 true
     * @param string $pathname 文件夹路径
     * @param bool $recursive 是否递归
     * @return bool
     */
    public static function rmdir(string $pathname, bool $recursive = false)
    {
        if (!is_dir($pathname)) {
            return true;
        }
        return $recursive ? static::rmdirRecursive($pathname) : rmdir($pathname);
    }

    /**
     * 递归删除文件夹
     * @param string $pathname
     * @return bool
     */
    private static function rmdirRecursive(string $pathname)
    {
        $test = true;
        static::glob($pathname, function($path) use (&$test){
            if ('/' === substr($path, -1)) {
                $test = static::rmdirRecursive($path);
            } else {
                $test = unlink($path);
            }
            if (!$test) {
                return null;
            }
            return false;
        });
        if ($test) {
            return rmdir($pathname);
        }
        return false;
    }

    /**
     * 获取指定文件夹下的所有 文件(夹), 如果指定文件夹不存在或无法读取, 返回 false
     * @param string $dir
     * @param ?callable $filter 过滤函数, 处理后返回:
     *                          true:正常；
     *                          false:返回文件列表中不包含；
     *                          null:直接中断,返回null之前的那个文件名将会是整个返回列表的最后一个；
     * @return array|false  返回数组中如果值最后一个字符是 / , 可认为这是一个文件夹
     */
    public static function glob(string $dir, callable $filter = null)
    {
        $dir = str_replace('\\', '/', realpath($dir));
        if (!$dir || !($handle = opendir($dir))) {
            return false;
        }
        $files = [];
        while (false !== ($file = readdir($handle))) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            $file = $dir . '/' . $file;
            $file .= is_dir($file) ? '/' : '';
            $test = $filter ? call_user_func($filter, $file) : true;
            if (null === $test) {
                break;
            } elseif ($test) {
                $files[] = $file;
            }
        }
        closedir($handle);
        return $files;
    }

    /**
     * 获取文件(夹)列表
     * @param string $dir 前缀限制
     * @param bool $expand 是否展开子文件夹, 展开会返回子文件夹的所有嵌套(包括文件夹本事)
     * @param ?string $marker 起始文件名, 对于超长列表, 可分页展示
     * @param int $max 返回文件最大数目
     * @return array
     */
    public static function lists(string $dir, bool $expand = false, string $marker = null, int $max = 100)
    {
        $contents = [];
        $truncated = false;
        $max = min(max($max, 1), 1000);
        $marker = empty($marker = (string) $marker) ? false : $marker;
        static::getLists($dir, $expand, function ($file) use ($max, &$marker, &$contents, &$truncated) {
            if (count($contents) >= $max) {
                $truncated = true;
                return true;
            }
            if (false === $marker) {
                $contents[] = $file;
            }
            if ($marker === $file) {
                $marker = false;
            }
            return false;
        });
        return [
            AccessInterface::TRUNCATED => $truncated,
            AccessInterface::CONTENTS => $contents
        ];
    }

    /**
     * 对指定文件夹循环获取,交给回调函数处理
     * @param string $dir
     * @param bool $expand
     * @param callable $callback
     * @return bool
     */
    private static function getLists(string $dir, bool $expand, callable $callback)
    {
        $end = false;
        static::glob($dir, function($file) use ($callback, $expand, &$end) {
            $end = call_user_func($callback, $file);
            if (!$end && $expand && '/' === substr($file, -1)) {
                $end = static::getLists($file, $expand, $callback);
            }
            return $end ? null : false;
        });
        return $end;
    }
}
