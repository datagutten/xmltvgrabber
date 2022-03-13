<?php

namespace datagutten\xmltv\tests\grabbers;

use datagutten\tools\files\files;
use datagutten\xmltv\grabbers\base\common;
use datagutten\xmltv\grabbers\exceptions;
use Exception;

class commonTest extends grabberTestCase
{
    /**
     * @var common
     */
    public common $common;

    public function setUp(): void
    {
        parent::setUp();
        $this->common = new common('test.no', 'nb');
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
}
