<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

namespace datagutten\xmltv\tests\grabbers;

use datagutten\tools\files\files;
use datagutten\xmltv\grabbers\base\common;
use datagutten\xmltv\grabbers\exceptions;
use Exception;

class DummyChannel extends common
{
    public static string $xmltv_id = 'test.no';
    public static string $language = 'nb';

    /**
     * Dummy method to call file with create=false to get coverage on exception handler
     * @param int $timestamp
     * @param string $extension
     * @return string
     * @throws exceptions\XMLTVError
     */
    public function local_file_no_create(int $timestamp, string $extension = 'html')
    {
        return $this->file($this->channel, $timestamp, 'raw_data', $extension, false);
    }

    public function grab(int $timestamp = 0): ?string
    {
        return $timestamp; //Dummy method to avoid error
    }
}

class commonTest extends grabberTestCase
{
    /**
     * @var DummyChannel
     */
    public DummyChannel $common;

    public function setUp(): void
    {
        parent::setUp();
        $this->common = new DummyChannel();
    }

    /**
     * @throws exceptions\ConnectionError
     * @throws exceptions\XMLTVError
     */
    public function testDownload()
    {
        $this->common->download('https://httpbin.org/get', 0, 'json');
        $file = $this->common->local_file(0, 'json');
        $this->assertFileExists($file);
    }

    /**
     * @throws Exception
     */
    public function testDownload_cache()
    {
        $this->common->download_cache('https://httpbin.org/get', 0, 'json');
        $file = $this->common->local_file(0, 'json');
        $this->assertFileExists($file);
        $data = $this->common->download_cache('foo', 0, 'json');
        $this->assertNotEmpty($data);
    }

    public function testLocal_file()
    {
        $file = $this->common->local_file(strtotime('2019-08-15'), 'json');
        $expected_file = files::path_join(__DIR__, 'xmltv_test', 'test.no', 'raw_data', '2019',
            'test.no_2019-08-15.json');
        $this->assertEquals($expected_file, $file);
    }

    public function testLocal_fileNotFound()
    {
        $this->expectException(exceptions\XMLTVError::class);
        $this->common->local_file_no_create(strtotime('2019-08-15'), 'json');
    }

    /**
     * save_file should return null when no programs are added
     * @throws exceptions\XMLTVError
     */
    function testSaveEmpty()
    {
        $this->common = new DummyChannel();
        $file = $this->common->save_file(time());
        $this->assertNull($file);
    }
}
