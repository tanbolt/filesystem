<?php
namespace Tanbolt\Filesystem;

interface DriverInterface extends AccessInterface
{
    /**
     * 设置配置选项
     * @param array $config
     * @return static
     */
    public function configure(array $config = []);
}
