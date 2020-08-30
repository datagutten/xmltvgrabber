<?php

namespace datagutten\xmltv\tests\grabbers;

use datagutten\xmltv\grabbers\base\common;
use Exception;
use PHPUnit\Framework\TestCase;
use Requests_Exception;
use Symfony\Component\Filesystem\Filesystem;

class commonTest extends TestCase
{
    /**
     * @var common
     */
    public $common;
    /**
     * @var Filesystem
     */
    public $filesystem;
    public function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $config = file_get_contents(__DIR__.'/test_config.php');
        $config = str_replace('__DIR__', __DIR__, $config);
        file_put_contents(__DIR__.'/config.php', $config);
        set_include_path(__DIR__);
        mkdir(__DIR__.'/xmltv_test');
        $this->common = new common('test.no', 'nb');
    }

    /**
     * @throws Requests_Exception
     */
    public function testDownload()
    {
        $this->common->download('https://httpbin.org/get', null, 'json');
        $file = $this->common->local_file(null, 'json');
        $this->assertFileExists($file);
    }

    /**
     * @throws Exception
     */
    public function testDownload_cache()
    {
        $this->common->download_cache('https://httpbin.org/get', null, 'json');
        $file = $this->common->local_file(null, 'json');
        $this->assertFileExists($file);
        $data = $this->common->download_cache('foo', null, 'json');
        $this->assertNotEmpty($data);
    }

    public function testLocal_file()
    {
        $file = $this->common->local_file(strtotime('2019-08-15'), 'json');
        $this->assertEquals(__DIR__.'/xmltv_test/test.no/raw_data/2019/test.no_2019-08-15.json', $file);
    }

    public function tearDown(): void
    {
        $this->filesystem->remove(__DIR__.'/xmltv_test');
        unlink(__DIR__.'/config.php');
    }
}
