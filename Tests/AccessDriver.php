<?php

use Tanbolt\Filesystem\File;
use PHPUnit\Framework\TestCase;
use Tanbolt\Filesystem\Filesystem;

class AccessDriver
{
    public static function startDriverTest($driver, $config)
    {
        $level = error_reporting();
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        $dir = '_phpunit_';
        $fs = new Filesystem();
        $fs->setDriver($driver, $config);
        try {
            static::runDriverTest($fs, $dir);
            $fs->rmdir($dir, true);
            error_reporting($level);
        } catch (Throwable $e) {
            $fs->rmdir($dir, true);
            error_reporting($level);
            throw $e;
        }
    }

    private static function runDriverTest(Filesystem $fs, string $dir)
    {
        $now = time();
        $file = $dir.'/filesystem.txt';

        TestCase::assertFalse($fs->has($file));
        TestCase::assertEquals(3, $fs->put($file, 'foo'));

        // basic
        TestCase::assertEquals('foo', $fs->getContent($file));
        TestCase::assertTrue($fs->has($dir));
        TestCase::assertTrue($fs->has($file));
        TestCase::assertEquals('dir', $fs->getFiletype($dir));
        TestCase::assertEquals('file', $fs->getFiletype($file));
        TestCase::assertEquals(3, $fs->getSize($file));
        TestCase::assertEquals('text/plain', $fs->getMimeType($file));
        $hash = $fs->getHash($file);
        TestCase::assertTrue($hash === null || (is_string($hash) && !empty($hash)));

        $fileLastModified = $fs->getLastModified($file);
        TestCase::assertLessThan(5, $fileLastModified - $now);

        $dirLastModified = $fs->getLastModified($file);
        TestCase::assertLessThan(5, $dirLastModified - $now);

        // getMetadata
        TestCase::assertEquals(static::ksort([
            'type' => 'file',
            'path' => '_phpunit_/filesystem.txt',
            'size' => 3,
            'lastModified' => $fileLastModified,
            'mimeType' => 'text/plain',
        ]), static::ksort($fs->getMetadata($file)));

        TestCase::assertEquals(static::ksort([
            'type' => 'dir',
            'path' => '_phpunit_/',
            'size' => 0,
            'lastModified' => $dirLastModified,
            'mimeType' => '',
        ]), static::ksort($fs->getMetadata($dir)));

        // windows 下不一定生效, 不测试这个了
        // $fs->setAcl($file, $fs::ACL_READ);
        // TestCase::assertEquals($fs::ACL_READ, $fs->getAcl($file));

        // getStream
        TestCase::assertEquals(3, $fs->append($file,'bar'));
        TestCase::assertEquals(6, $fs->getSize($file));
        $stream = $fs->getStream($file);
        TestCase::assertEquals('foo', fread($stream, 3));
        TestCase::assertEquals('bar', fread($stream, 3));
        fclose($stream);

        TestCase::assertEquals(3, $fs->prepend($file,'que'));
        TestCase::assertEquals(9, $fs->getSize($file));
        $stream = $fs->getStream($file);
        TestCase::assertEquals('que', fread($stream, 3));
        TestCase::assertEquals('foo', fread($stream, 3));
        TestCase::assertEquals('bar', fread($stream, 3));
        fclose($stream);

        // copy
        $fileCopy = $dir.'/filesystem2.txt';
        TestCase::assertTrue($fs->copy($file, $fileCopy));
        TestCase::assertTrue($fs->has($fileCopy));
        TestCase::assertEquals('quefoobar', $fs->getContent($fileCopy));
        TestCase::assertFalse($fs->copy($file, $fileCopy, false));
        $fs->put($fileCopy, 'foo');
        TestCase::assertEquals('foo', $fs->getContent($fileCopy));

        // rename
        TestCase::assertFalse($fs->rename($file, $fileCopy, false));
        TestCase::assertTrue($fs->rename($file, $fileCopy));
        TestCase::assertFalse($fs->has($file));
        TestCase::assertEquals('quefoobar', $fs->getContent($fileCopy));

        // unlink
        TestCase::assertTrue($fs->unlink($file));
        TestCase::assertTrue($fs->unlink($fileCopy));
        TestCase::assertFalse($fs->has($fileCopy));

        // 文件夹操作测试
        $source = $dir.'/source';
        $dest = $dir.'/dest';

        /*
         * mkdir:
         * /source/deep
         * /dest
         */
        $deep = $source.'/deep';
        TestCase::assertFalse($fs->mkdir($deep, false));
        TestCase::assertFalse($fs->has($deep));
        TestCase::assertTrue($fs->mkdir($deep));
        TestCase::assertTrue($fs->has($deep));
        TestCase::assertTrue($fs->mkdir($dest, false));
        TestCase::assertTrue($fs->has($dest));

        /*
         * [prepare for cpdir / mvdir] [test lists]
         * /source/foo.txt
         * /source/que.txt
         * /source/deep/bar.txt
         * /source/deep/que.txt
         * /source/deep/lst/lst.txt
         *
         * /dest/foo.txt
         * /dest/deep/bar.txt
         *
         */
        $source_files = [
            '/foo.txt',
            '/que.txt',
            '/deep/',
            '/deep/bar.txt',
            '/deep/quz.txt',
            '/deep/lst/',
            '/deep/lst/lst.txt',
        ];
        $dest_files = [
            '/exist.txt',
            '/foo.txt',
            '/deep/',
            '/deep/exist.txt',
            '/deep/bar.txt',
        ];
        $merge_files = array_merge($source_files, [
            '/exist.txt',
            '/deep/exist.txt',
        ]);
        TestCase::assertTrue($fs->put($source.'/foo.txt', 'foo'));
        TestCase::assertTrue($fs->put($source.'/que.txt', 'que'));
        TestCase::assertTrue($fs->put($source.'/deep/bar.txt', 'bar'));
        TestCase::assertTrue($fs->put($source.'/deep/quz.txt', 'quz'));
        TestCase::assertTrue($fs->put($source.'/deep/lst/lst.txt', 'lst'));

        TestCase::assertTrue($fs->put($dest.'/exist.txt', 'exist'));
        TestCase::assertTrue($fs->put($dest.'/foo.txt', 'foo2'));
        TestCase::assertTrue($fs->put($dest.'/deep/exist.txt', 'exist'));
        TestCase::assertTrue($fs->put($dest.'/deep/bar.txt', 'bar2'));

        TestCase::assertEquals(
            static::sort($source_files, $source),
            static::sort($fs->lists($source, true)[$fs::CONTENTS])
        );
        TestCase::assertEquals(
            static::sort($dest_files, $dest),
            static::sort($fs->lists($dest, true)[$fs::CONTENTS])
        );

        /*
         * cpdir
         */
        $source_copy = $dir.'/source_copy';
        $dest_copy = $dir.'/dest_copy';
        TestCase::assertTrue($fs->cpdir($source, $source_copy));
        TestCase::assertTrue($fs->cpdir($dest, $dest_copy));
        TestCase::assertEquals(
            static::sort($source_files, $source_copy),
            static::sort($fs->lists($source_copy, true)[$fs::CONTENTS])
        );
        TestCase::assertEquals(
            static::sort($dest_files, $dest_copy),
            static::sort($fs->lists($dest_copy, true)[$fs::CONTENTS])
        );

        // 测试 overwrite=true
        TestCase::assertTrue($fs->cpdir($source, $dest));
        TestCase::assertEquals(
            static::sort($merge_files, $dest),
            static::sort($fs->lists($dest, true)[$fs::CONTENTS])
        );
        TestCase::assertEquals('foo', $fs->getContent($dest.'/foo.txt'));
        TestCase::assertEquals('bar', $fs->getContent($dest.'/deep/bar.txt'));

        // 复原 dest 目录
        TestCase::assertTrue($fs->rmdir($dest, true));
        TestCase::assertFalse($fs->has($dest));
        TestCase::assertTrue($fs->cpdir($dest_copy, $dest));

        // 测试 overwrite=false
        TestCase::assertTrue($fs->cpdir($source, $dest, false));
        TestCase::assertEquals(
            static::sort($merge_files, $dest),
            static::sort($fs->lists($dest, true)[$fs::CONTENTS])
        );
        TestCase::assertEquals('foo2', $fs->getContent($dest.'/foo.txt'));
        TestCase::assertEquals('bar2', $fs->getContent($dest.'/deep/bar.txt'));

        // 复原 dest 目录
        TestCase::assertTrue($fs->rmdir($dest, true));
        TestCase::assertTrue($fs->cpdir($dest_copy, $dest));

        /*
         * mvdir
         */
        // 测试 overwrite=true
        TestCase::assertTrue($fs->mvdir($source, $dest));
        TestCase::assertEquals(
            static::sort($merge_files, $dest),
            static::sort($fs->lists($dest, true)[$fs::CONTENTS])
        );
        TestCase::assertEquals('foo', $fs->getContent($dest.'/foo.txt'));
        TestCase::assertEquals('bar', $fs->getContent($dest.'/deep/bar.txt'));
        TestCase::assertFalse($fs->has($source));

        // 复原 source dest 目录
        TestCase::assertTrue($fs->cpdir($source_copy, $source));
        TestCase::assertTrue($fs->rmdir($dest, true));
        TestCase::assertTrue($fs->cpdir($dest_copy, $dest));

        // 测试 overwrite=false
        TestCase::assertTrue($fs->mvdir($source, $dest, false));
        TestCase::assertEquals(
            static::sort($merge_files, $dest),
            static::sort($fs->lists($dest, true)[$fs::CONTENTS])
        );
        TestCase::assertEquals('foo2', $fs->getContent($dest.'/foo.txt'));
        TestCase::assertEquals('bar2', $fs->getContent($dest.'/deep/bar.txt'));
        TestCase::assertTrue($fs->has($source));
        TestCase::assertEquals(
            static::sort([
                '/foo.txt',
                '/deep/',
                '/deep/bar.txt',
            ], $source),
            static::sort($fs->lists($source, true)[$fs::CONTENTS])
        );

        // 清理, 仅保留 dest
        TestCase::assertTrue($fs->rmdir($source, true));
        TestCase::assertTrue($fs->rmdir($source_copy, true));
        TestCase::assertTrue($fs->rmdir($dest_copy, true));

        /*
         * cleandir
         */
        $emptyDirs = [
            '/empty/',
            '/deep/empty/',
            '/deep/lst/empty/',
        ];
        foreach ($emptyDirs as $empty) {
            TestCase::assertTrue($fs->mkdir($dest.$empty));
        }
        TestCase::assertEquals(
            static::sort(array_merge($merge_files, $emptyDirs), $dest),
            static::sort($fs->lists($dest, true)[$fs::CONTENTS])
        );
        TestCase::assertTrue($fs->cleandir($dest, true));
        TestCase::assertEquals(
            static::sort($merge_files, $dest),
            static::sort($fs->lists($dest, true)[$fs::CONTENTS])
        );
        TestCase::assertTrue($fs->rmdir($dest, true));
        TestCase::assertFalse($fs->has($dest));

        // prepare
        foreach ($emptyDirs as $empty) {
            TestCase::assertTrue($fs->mkdir($dest.$empty));
        }
        $emptyDirs = array_merge($emptyDirs, [
            '/deep/',
            '/deep/lst/',
        ]);
        TestCase::assertTrue($fs->cpdir($dest, $dest_copy));
        TestCase::assertEquals(
            static::sort($emptyDirs, $dest),
            static::sort($fs->lists($dest, true)[$fs::CONTENTS])
        );
        TestCase::assertEquals(
            static::sort($emptyDirs, $dest_copy),
            static::sort($fs->lists($dest_copy, true)[$fs::CONTENTS])
        );

        // $includeSelf=true
        TestCase::assertTrue($fs->cleandir($dest, true));
        TestCase::assertFalse($fs->has($dest));

        // $includeSelf=false
        TestCase::assertTrue($fs->cleandir($dest_copy, false));
        TestCase::assertTrue($fs->has($dest_copy));
        TestCase::assertEquals(
            [],
            static::sort($fs->lists($dest_copy, true)[$fs::CONTENTS])
        );

        static::runFileTest($fs, $file);
    }

    public static function runFileTest(Filesystem $fs, string $dir)
    {
        $now = time();
        $path = $dir.'/filesystem.log';
        TestCase::assertEquals(3, $fs->put($path, 'log'));
        TestCase::assertTrue($fs->has($path));
        $file = $fs->getObject($path);
        TestCase::assertInstanceOf(File::class, $file);

        // path url
        $domain = $fs->getDomain();
        TestCase::assertSame($fs, $file->filesystem);
        TestCase::assertEquals($path, $file->path);
        TestCase::assertEquals($domain.'/'.$path, $file->url);
        $fs->setDomain('foo.com');
        TestCase::assertEquals('foo.com/'.$path, $file->url);
        $fs->setDomain($domain);

        TestCase::assertEquals($dir, $file->dirname);
        TestCase::assertEquals('filesystem.log', $file->basename);
        TestCase::assertEquals('filesystem', $file->filename);
        TestCase::assertEquals('log', $file->extension);

        // basic
        $lastModified = $file->lastModified;
        TestCase::assertEquals('file', $file->filetype);
        TestCase::assertEquals(3, $file->size);
        TestCase::assertEquals('text/plain', $file->mimeType);
        TestCase::assertLessThan(5, $lastModified - $now);

        $hash = $file->hash;
        TestCase::assertTrue($hash === null || (is_string($hash) && !empty($hash)));

        // getMetadata
        TestCase::assertEquals(static::ksort([
            'type' => 'file',
            'path' => $path,
            'size' => 3,
            'lastModified' => $lastModified,
            'mimeType' => 'text/plain',
        ]), static::ksort($file->metadata));


        // windows 下不一定生效, 不测试这个了
        // $fs->setAcl($file, $fs::ACL_READ);
        // TestCase::assertEquals($fs::ACL_READ, $file->acl);

        // content / put
        TestCase::assertEquals('log', $file->content);
        TestCase::assertEquals(3, $file->put('foo'));
        TestCase::assertEquals('foo', $file->content);

        // append / stream
        TestCase::assertEquals(3, $file->append('bar'));
        TestCase::assertEquals(6, $file->size);
        $stream = $file->stream;
        TestCase::assertSame($stream, $file->stream);
        TestCase::assertEquals('foo', fread($file->stream, 3));
        TestCase::assertEquals('bar', fread($file->stream, 3));
        fclose($file->stream);

        // prepend / stream
        TestCase::assertEquals(3, $file->prepend('que'));
        TestCase::assertEquals(9, $file->size);
        $newStream = $file->stream;
        TestCase::assertNotSame($stream, $newStream);
        TestCase::assertSame($newStream, $file->stream);
        TestCase::assertEquals('que', fread($newStream, 3));
        TestCase::assertEquals('foo', fread($newStream, 3));
        TestCase::assertEquals('bar', fread($newStream, 3));
        fclose($newStream);

        // copy
        $newPath = $dir.'/filesystem2.log';
        $newFile = $file->copy($newPath);
        TestCase::assertTrue($fs->has($path));
        TestCase::assertTrue($fs->has($newPath));
        TestCase::assertInstanceof(File::class, $newFile);
        TestCase::assertNotSame($file, $newFile);
        TestCase::assertEquals($newPath, $newFile->path);
        TestCase::assertTrue($newFile->unlink());
        TestCase::assertFalse($fs->has($newPath));

        // rename
        TestCase::assertTrue($file->rename($newPath));
        TestCase::assertFalse($fs->has($path));
        TestCase::assertTrue($fs->has($newPath));
        TestCase::assertEquals($newPath, $file->path);
        TestCase::assertTrue($file->unlink());
        TestCase::assertFalse($fs->has($newPath));
    }

    private static function ksort($arr)
    {
        ksort($arr);
        return $arr;
    }

    private static function sort($arr, $prefix = null)
    {
        if ($prefix) {
            $arr = array_map(function ($file) use ($prefix) {
                return $prefix.$file;
            }, $arr);
        }
        sort($arr);
        return $arr;
    }
}
