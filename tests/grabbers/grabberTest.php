<?php

namespace datagutten\xmltv\tests\grabbers;

use datagutten\xmltv\grabbers;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class grabberTest extends TestCase
{
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
    }

    /**
     * @param string $id Channel id
     * @param string $grabber_class Grabber class name
     * @dataProvider grabberProvider
     */
    function testGrab($id, $grabber_class)
    {
        /**
         * @var $grabber grabbers\base\common
         */
        $grabber = new $grabber_class;
        $file = $grabber->grab();
        $this->assertNotEmpty($file);
        $this->assertFileExists($file);
        $this->assertEquals($id, $grabber->channel);
        $this->assertStringStartsWith($id, basename($file));
        $xml = simplexml_load_file($file);
        if(empty($xml->{'programme'}))
            $this->fail('<programme> missing or empty');
    }
    public function tearDown(): void
    {
        $this->filesystem->remove(__DIR__.'/xmltv_test');
        unlink(__DIR__.'/config.php');
    }

    public function grabberProvider()
    {
        $grabbers = [];
        foreach(grabbers\grabbers::getGrabbers() as $id=>$grabber)
        {
            $grabbers[] = [$id, $grabber];
        }
        return $grabbers;
    }

    public function dateProvider()
    {
        $dates = [];
        foreach (array_keys(grabbers\base\discovery_no::$channels) as $channel) {
            $dates[] = [$channel, '2011-06-01'];
        }

        foreach (array_keys(grabbers\base\disney_no::$channels) as $channel) {
            $dates[] = [$channel, '2011-06-01'];
        }

        return $dates;
    }

    /**
     * @param string $channel Channel id
     * @param string $date Date
     * @dataProvider dateProvider
     * @requires PHPUnit 9.1
     */
    public function testInvalidDate($channel, $date)
    {
        $grabber = grabbers\grabbers::grabber($channel);
        /**
         * @var $grabber grabbers\base\common
         */
        $grabber = new $grabber;
        $result = $grabber->grab(strtotime($date));
        $this->assertEmpty($result);
        $file = $grabber->files->file($grabber->channel, strtotime($date));
        $this->assertFileDoesNotExist($file);
    }

    public function testGetGrabber()
    {
        $class = grabbers\grabbers::grabber('max.no');
        $this->assertEquals(grabbers\discovery_max_no::class, $class);
    }

    public function testInvalidGrabber()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No grabber for test.no');
        grabbers\grabbers::grabber('test.no');
    }
}
