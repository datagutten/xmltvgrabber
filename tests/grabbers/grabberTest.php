<?php

namespace datagutten\xmltv\tests\grabbers;

use datagutten\xmltv\grabbers;
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
     * @param string $grabber_class Grabber class name
     * @dataProvider grabberProvider
     */
    function testGrab($grabber_class)
    {
        /**
         * @var $grabber grabbers\common
         */
        $grabber = new $grabber_class;
        $file = $grabber->grab();
        $this->assertFileExists($file);
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
        foreach(grabbers\grabbers::getGrabbers() as $grabber)
        {
            $grabbers[] = [$grabber];
        }
        return $grabbers;
    }
}
