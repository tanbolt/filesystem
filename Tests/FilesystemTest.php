<?php

use PHPUnit\Framework\TestCase;
use Tanbolt\Filesystem\File;
use Tanbolt\Filesystem\Filesystem;
use Tanbolt\Filesystem\Driver\Local;
use Tanbolt\Filesystem\Driver\AliYun;
use Tanbolt\Filesystem\Exception\InvalidDriverException;

class FilesystemTest extends TestCase
{
    protected function setUp():void
    {
        require_once __DIR__.'/AccessDriver.php';
    }

    public function testFilesystemInstance()
    {
        $fs = new Filesystem();
        static::assertNull($fs->getDomain());
        try {
            $fs->getDriver();
            static::fail('It should throw if driver not configure');
        } catch (InvalidDriverException $e) {
            static::assertTrue(true);
        }

        $fs = new Filesystem('foo.com', 'local', [
            'root' => __DIR__
        ]);
        static::assertEquals('foo.com', $fs->getDomain());
        static::assertInstanceOf(Local::class, $fs->getDriver());

        $fs2 = $fs->instance();
        static::assertInstanceOf(Filesystem::class, $fs2);
        static::assertNotSame($fs, $fs2);
    }

    public function testSetDomain()
    {
        $fs = new Filesystem();
        static::assertNull($fs->getDomain());
        static::assertSame($fs, $fs->setDomain('foo.com'));
        static::assertEquals('foo.com', $fs->getDomain());
        $fs->setDomain(null);
        static::assertNull($fs->getDomain());
    }

    public function testSetDriver()
    {
        $fs = new Filesystem();
        static::assertSame($fs, $fs->setDriver('local'));
        static::assertInstanceOf(Local::class, $fs->getDriver());
    }

    public function testSetListener()
    {
        $fs = new Filesystem();
        static::assertSame($fs, $fs->setDriver('local'));
        static::assertInstanceOf(Local::class, $fs->getDriver());

        $aliYun = $fs->getDriver('aliYun');
        static::assertInstanceOf(AliYun::class, $aliYun);
    }

    public function testGetObject()
    {
        $fs = new Filesystem('foo.com', 'local', [
            'root' => __DIR__.'/Fixtures'
        ]);
        $file = $fs->getObject('test.jpg');
        static::assertInstanceOf(File::class, $file);
    }

    public function testGetUrl()
    {
        $fs = new Filesystem('foo.com');
        static::assertEquals('foo.com/test.jpg', $fs->getUrl('test.jpg'));
        $fs->setDomain('bar.com/file');
        static::assertEquals('bar.com/file/test.jpg', $fs->getUrl('test.jpg'));
        $fs->setDomain('bar.com/files/');
        static::assertEquals('bar.com/files/test.jpg', $fs->getUrl('test.jpg'));
    }

    /**
     * @dataProvider getDriver
     * @param $driver
     * @param $config
     * @throws Throwable
     */
    public function testDriverAccessFile($driver, $config)
    {
        AccessDriver::startDriverTest($driver, $config);
    }

    // 测试驱动器
    public function getDriver()
    {
        return [
            ['local', ['root' => __DIR__.'/Fixtures']]
        ];
    }
}
