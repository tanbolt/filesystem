<?php
namespace Tanbolt\Filesystem;

interface FilesystemInterface extends AccessInterface
{
    /**
     * 设置附件绑定域名
     * @param ?string $domain
     * @return static
     */
    public function setDomain(?string $domain);

    /**
     * 设置文件管理系统的接口
     * @param DriverInterface|string $driver
     * @param array $config
     * @return static
     */
    public function setDriver($driver, array $config = []);

    /**
     * 设置文件操作回调函数
     * @param ?callable $listener
     * @return static
     */
    public function setListener(callable $listener = null);

    /**
     * 由文件绝对路径获取其 URL
     * @param string $filename
     * @return string
     */
    public function getUrl(string $filename);

    /**
     * 获取指定文件(夹) 的对象
     * @param string $filename
     * @return File
     */
    public function getObject(string $filename);
}
