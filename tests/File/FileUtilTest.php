<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\UtilsBundle\Tests\File;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\DataContainer;
use Contao\File;
use Contao\FilesModel;
use Contao\Folder;
use Contao\System;
use HeimrichHannot\UtilsBundle\Arrays\ArrayUtil;
use HeimrichHannot\UtilsBundle\Container\ContainerUtil;
use HeimrichHannot\UtilsBundle\File\FileUtil;
use HeimrichHannot\UtilsBundle\String\StringUtil;
use HeimrichHannot\UtilsBundle\Tests\TestCaseEnvironment;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;

class FileUtilTest extends TestCaseEnvironment
{
    public static function tearDownAfterClass(): void
    {
        // The temporary directory would not be removed without this call!
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        parent::setUp();

        $fs = new Filesystem();
        $fs->mkdir(TL_ROOT.\DIRECTORY_SEPARATOR.$this->getTempDir().'/files/');

        $arrayUtils = new ArrayUtil($this->mockContaoFramework());
        $container = $this->mockContainer();
        $container->set('huh.utils.array', $arrayUtils);

        $filesModel = $this->mockClassWithProperties(FilesModel::class, ['path' => $this->getTempDir().'/files']);
        $filesAdapter = $this->mockAdapter(['findByUuid']);
        $filesAdapter->method('findByUuid')->willReturn($filesModel);
        $container->set('contao.framework', $this->mockContaoFramework([FilesModel::class => $filesAdapter]));

        $utilsString = new StringUtil($this->mockContaoFramework());
        $container->set('huh.utils.string', $utilsString);

        $containerUtils = new ContainerUtil($this->mockContaoFramework(), $this->createMock(FileLocator::class), $this->createMock(ScopeMatcher::class));
        $container->set('huh.utils.container', $containerUtils);
        $container->setParameter('kernel.project_dir', TL_ROOT);
        System::setContainer($container);

        if (!\function_exists('standardize')) {
            include_once __DIR__.'/../../vendor/contao/core-bundle/src/Resources/contao/helper/functions.php';
        }
    }

    public function testGetFileList()
    {
        $fileUtil = new FileUtil($this->mockContaoFramework());
        file_put_contents(TL_ROOT.$this->getTempDir().'/files/testfile1', 'test');

        $fileList = $fileUtil->getFileList(TL_ROOT.$this->getTempDir().'/files', __DIR__, 'protectBaseUrl');
        $this->assertSame('protectBaseUrl?file='.__DIR__.'/testfile1', $fileList[0]['absUrl']);

        file_put_contents(TL_ROOT.$this->getTempDir().'/files/testfile2', 'test');
        file_put_contents(TL_ROOT.$this->getTempDir().'/files/testfile3', 'test');

        $fileList = $fileUtil->getFileList(TL_ROOT.$this->getTempDir().'/files', __DIR__);

        $this->assertCount(3, $fileList);
        $this->assertArrayHasKey(0, $fileList);
        $this->assertArrayHasKey('filename', $fileList[0]);
        $this->assertNotSame('', $fileList[0]['filename']);
        $this->assertArrayHasKey(1, $fileList);
        $this->assertArrayHasKey('filename', $fileList[1]);
        $this->assertNotSame('', $fileList[1]['filename']);
        $this->assertArrayHasKey(2, $fileList);
        $this->assertArrayHasKey('filename', $fileList[2]);
        $this->assertNotSame('', $fileList[2]['filename']);

        $fileList = $fileUtil->getFileList($this->getTempDir().'/fileList', __DIR__);

        $this->assertCount(0, $fileList);
    }

    public function testGetUniqueFileNameWithinTarget()
    {
        $framework = $this->mockContaoFramework();
        $fileUtil = new FileUtil($framework);

        $fileName = $fileUtil->getUniqueFileNameWithinTarget($this->getTempDir().'/files/test', 'te');
        $this->assertSame(ltrim($this->getTempDir().'/files/_1.', '/'), $fileName);

        $fileName = $fileUtil->getUniqueFileNameWithinTarget($this->getTempDir().'/test/test/test');
        $this->assertFalse($fileName);

        file_put_contents(TL_ROOT.$this->getTempDir().'/files/test', 'test');
        $fileName = $fileUtil->getUniqueFileNameWithinTarget($this->getTempDir().'/files/test');
        $this->assertSame(ltrim($this->getTempDir().'/files/test_1.', '/'), $fileName);

        file_put_contents(TL_ROOT.$this->getTempDir().'/files/test_10', 'test');
        $fileName = $fileUtil->getUniqueFileNameWithinTarget($this->getTempDir().'/files/test_10', null, 10);
        $this->assertNotSame(ltrim($this->getTempDir().'/files/test', '/'), $fileName);

        $fileName = $fileUtil->getUniqueFileNameWithinTarget($this->getTempDir().'/files/test', null, 100);
        $this->assertNotSame(ltrim($this->getTempDir().'/files/test', '/'), $fileName);
    }

    public function testFormatSizeUnits()
    {
        $framework = $this->mockContaoFramework();
        $fileUtil = new FileUtil($framework);

        $bytes = $fileUtil->formatSizeUnits(1073741824);
        $this->assertSame('1.00 GB', $bytes);
        $bytes = $fileUtil->formatSizeUnits(1048576);
        $this->assertSame('1.00 MB', $bytes);
        $bytes = $fileUtil->formatSizeUnits(1024);
        $this->assertSame('1.00 KB', $bytes);
        $bytes = $fileUtil->formatSizeUnits(3);
        $this->assertSame('3 Bytes', $bytes);
        $bytes = $fileUtil->formatSizeUnits(1);
        $this->assertSame('1 Byte', $bytes);
        $bytes = $fileUtil->formatSizeUnits(10737.41824);
        $this->assertSame('10.49 KB', $bytes);

        try {
            $bytes = $fileUtil->formatSizeUnits('107374,1824');
        } catch (\Exception $exception) {
            $this->assertSame('A non well formed numeric value encountered', $exception->getMessage());
        }
        $bytes = $fileUtil->formatSizeUnits(1073741894, true);
        $this->assertSame('1.00&nbsp;GB', $bytes);
        $bytes = $fileUtil->formatSizeUnits(0.1073741894, true);
        $this->assertSame('0&nbsp;Bytes', $bytes);
    }

    public function testGetPathWithoutFilename()
    {
        $framework = $this->mockContaoFramework();
        $fileUtil = new FileUtil($framework);
        $path = $fileUtil->getPathWithoutFilename($this->getTempDir().'/file/testfile1');
        $this->assertSame($this->getTempDir().'/file', $path);

        $path = $fileUtil->getPathWithoutFilename('');
        $this->assertSame('', $path);

        $path = $fileUtil->getPathWithoutFilename(1234);
        $this->assertSame('.', $path);
    }

    public function testGetFileExtension()
    {
        $framework = $this->mockContaoFramework();
        $fileUtil = new FileUtil($framework);
        $fileExtension = $fileUtil->getFileExtension($this->getTempDir().'/file/testfile1');
        $this->assertSame('', $fileExtension);
        $fileExtension = $fileUtil->getFileExtension($this->getTempDir().'/file/testfile1.txt');
        $this->assertSame('txt', $fileExtension);
        $fileExtension = $fileUtil->getFileExtension($this->getTempDir().'/file/testfile1.xml');
        $this->assertSame('xml', $fileExtension);
        $fileExtension = $fileUtil->getFileExtension($this->getTempDir().'/file/testfile1...xml');
        $this->assertSame('xml', $fileExtension);
        $fileExtension = $fileUtil->getFileExtension($this->getTempDir().'');
        $this->assertSame('', $fileExtension);
        $fileExtension = $fileUtil->getFileExtension($this->getTempDir().'.xml');
        $this->assertSame('xml', $fileExtension);
        $fileExtension = $fileUtil->getFileExtension('');
        $this->assertSame('', $fileExtension);
        $fileExtension = $fileUtil->getFileExtension(1234);
        $this->assertSame('', $fileExtension);
    }

    public function testAddUniqueIdToFilename()
    {
        $framework = $this->mockContaoFramework();
        $fileUtil = new FileUtil($framework);

        $file = $fileUtil->addUniqueIdToFilename('testFile');
        $this->assertNotSame('testFile', $file);
    }

    public function testSanitizeFileName()
    {
        $framework = $this->mockContaoFramework();
        $fileUtil = new FileUtil($framework);

        $fileName = $fileUtil->sanitizeFileName('fileName');
        $this->assertSame('filename', $fileName);

        $fileName = $fileUtil->sanitizeFileName('fileName', 3);
        $this->assertSame('fi', $fileName);

        $fileName = $fileUtil->sanitizeFileName('საბეჭდი_მანქანა');
        $this->assertSame('_', $fileName);
    }

    public function testGetFilesFromUuid()
    {
        $filesModel = $this->mockClassWithProperties(FilesModel::class, ['path' => $this->getTempDir().'/files']);
        $filesAdapter = $this->mockAdapter(['findByUuid']);
        $filesAdapter->method('findByUuid')->willReturn($filesModel);
        $fileUtil = new FileUtil($this->mockContaoFramework([FilesModel::class => $filesAdapter]));

        $file = $fileUtil->getFileFromUuid('uuid');
        $this->assertNull($file);

        file_put_contents(TL_ROOT.$this->getTempDir().'/files/testFile', 'test');
        $filesModel = $this->mockClassWithProperties(FilesModel::class, ['path' => $this->getTempDir().'/files/testFile']);
        $filesAdapter = $this->mockAdapter(['findByUuid']);
        $filesAdapter->method('findByUuid')->willReturn($filesModel);
        $fileUtil = new FileUtil($this->mockContaoFramework([FilesModel::class => $filesAdapter]));

        $file = $fileUtil->getFileFromUuid('uuid');
        $this->assertInstanceOf(File::class, $file);

        $filesAdapter = $this->mockAdapter(['findByUuid']);
        $filesAdapter->method('findByUuid')->willReturn(null);
        $fileUtil = new FileUtil($this->mockContaoFramework([FilesModel::class => $filesAdapter]));
        $file = $fileUtil->getFileFromUuid('uuid');
        $this->assertNull($file);
    }

    public function testGetPathFromUuid()
    {
        $filesModel = $this->mockClassWithProperties(FilesModel::class, ['path' => substr($this->getTempDir(), 1).'/files/testfile1']);
        $filesAdapter = $this->mockAdapter(['findByUuid']);
        $filesAdapter->method('findByUuid')->willReturn($filesModel);
        $fileUtil = new FileUtil($this->mockContaoFramework([FilesModel::class => $filesAdapter]));

        $path = $fileUtil->getPathFromUuid($this->getTempDir().'/files', false);
        $this->assertSame(substr($this->getTempDir(), 1).'/files/testfile1', $path);

        $path = $fileUtil->getPathFromUuid($this->getTempDir().'/files');
        $this->assertSame(substr($this->getTempDir(), 1).'/files/testfile1', $path);

        $filesAdapter = $this->mockAdapter(['findByUuid']);
        $filesAdapter->method('findByUuid')->willReturn(null);
        $framework = $this->mockContaoFramework([FilesModel::class => $filesAdapter]);
        $fileUtil = new FileUtil($framework);

        $path = $fileUtil->getPathFromUuid($this->getTempDir().'/files');
        $this->assertNull($path);
    }

    public function testGetFolderFromUuid()
    {
        $filesModel = $this->mockClassWithProperties(FilesModel::class, ['path' => $this->getTempDir().'/files']);
        $filesAdapter = $this->mockAdapter(['findByUuid']);
        $filesAdapter->method('findByUuid')->willReturn($filesModel);
        $fileUtil = new FileUtil($this->mockContaoFramework([FilesModel::class => $filesAdapter]));

        $path = $fileUtil->getFolderFromUuid('uuid');
        $this->assertInstanceOf(Folder::class, $path);

        $filesAdapter = $this->mockAdapter(['findByUuid']);
        $filesAdapter->method('findByUuid')->willReturn(null);
        $fileUtil = new FileUtil($this->mockContaoFramework([FilesModel::class => $filesAdapter]));
        $path = $fileUtil->getFolderFromUuid('uuid');
        $this->assertFalse($path);
    }

    public function testGetFileLineCount()
    {
        file_put_contents(TL_ROOT.$this->getTempDir().'/files/testFile', 'test');

        $framework = $this->mockContaoFramework();
        $fileUtil = new FileUtil($framework);

        $lines = $fileUtil->getFileLineCount($this->getTempDir().'/files/testFile');
        $this->assertSame(1, $lines);

        $lines = $fileUtil->getFileLineCount('foo');
        $this->assertTrue(false !== strpos($lines, 'fopen('.TL_ROOT.'/foo): failed to open stream:'));
    }

    public function testGetFolderFromDca()
    {
        $filesModel = $this->mockClassWithProperties(FilesModel::class, ['path' => $this->getTempDir().'/files']);
        $filesAdapter = $this->mockAdapter(['findByUuid']);
        $filesAdapter->method('findByUuid')->willReturn($filesModel);
        $fileUtil = new FileUtil($this->mockContaoFramework([FilesModel::class => $filesAdapter]));
        $folder = $fileUtil->getFolderFromDca($this->getTempDir().'/files');
        $this->assertSame($this->getTempDir().'/files', $folder);

        $folder = $fileUtil->getFolderFromDca('3712c116-1193-11e8-b642-0ed5f89f718b');
        $this->assertSame($this->getTempDir().'/files', $folder);

        $file = new File($this->getTempDir().'/files/dcaFile');
        $folder = $fileUtil->getFolderFromDca($file);
        $this->assertSame($this->getTempDir().'/files/dcaFile', $folder);

        $file = $this->mockClassWithProperties(FilesModel::class, ['path' => $this->getTempDir().'/files/dcaFile']);
        $folder = $fileUtil->getFolderFromDca($file);
        $this->assertSame($this->getTempDir().'/files/dcaFile', $folder);

        $folder = $fileUtil->getFolderFromDca(function ($dca) {
            return $this->getTempDir().'/files/dcaFile';
        }, $this->getDataContainerMock());
        $this->assertSame($this->getTempDir().'/files/dcaFile', $folder);

        $folder = $fileUtil->getFolderFromDca([self::class, 'getFolder'], $this->getDataContainerMock());
        $this->assertSame($this->getTempDir().'/files', $folder);

        try {
            $fileUtil->getFolderFromDca('dlfjn../ds');
        } catch (\Exception $exception) {
            $this->assertSame('Invalid target path dlfjn../ds', $exception->getMessage());
        }
    }

    /**
     * @return DataContainer|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getDataContainerMock($properties = true)
    {
        if ($properties) {
            return $this->mockClassWithProperties(DataContainer::class, ['id' => 1, 'table' => 'testTable']);
        }

        return $this->createMock(DataContainer::class);
    }

    public function getFolder($dca)
    {
        return $this->getTempDir().'/files';
    }
}
