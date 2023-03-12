<?php

namespace datagutten\xmltv\tests\grabbers;

use datagutten\xmltv\grabbers;
use InvalidArgumentException;

class grabberTest extends grabberTestCase
{
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
        $this->assertStringStartsWith($grabber::$xmltv_id, basename($file));
        $xml = simplexml_load_file($file);
        if(empty($xml->{'programme'}))
            $this->fail('<programme> missing or empty');
    }

    public function grabberProvider()
    {
        $grabbers = [];
        foreach(grabbers\grabbers::getGrabbers() as $id=>$grabber)
        {
            if (!in_array($id, ['disneychannel.no', 'junior.disneychannel.no']))
                $grabbers[] = [$id, $grabber];
        }
        return $grabbers;
    }

    public function dateProvider()
    {
        $dates = [];
        foreach (grabbers\grabbers::getGrabbers() as $id=>$grabber)
        {
            $parents = class_parents($grabber);
            if (in_array('datagutten\xmltv\grabbers\base\nrk', $parents))
                $dates[$id] = [$grabber, '1900-01-01'];
            else
                $dates[$id] = [$grabber, '2011-06-01'];
        }

        return $dates;
    }

    /**
     * Most grabbers have limited history capability, use that to test if the grabber handles invalid dates
     * @param string $grabber Grabber class
     * @param string $date Date
     * @throws grabbers\exceptions\GrabberException
     * @dataProvider dateProvider
     * @requires PHPUnit 9.1
     */
    public function testInvalidDate(string $grabber, string $date)
    {
        /**
         * @var $grabber grabbers\base\common
         */
        $grabber = new $grabber;
        $this->expectException(grabbers\exceptions\GrabberException::class);
        $grabber->grab(strtotime($date));
    }

    public function testGetGrabber()
    {
        $class = grabbers\grabbers::grabber('max.no');
        $this->assertEquals(grabbers\discovery_max_no::class, $class);
    }

    public function testGetGrabbers()
    {
        $grabbers = grabbers\grabbers::getGrabbers();
        $this->assertArrayHasKey('nyhet.tv2.no', $grabbers);
    }

    public function testInvalidGrabber()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No grabber for test.no');
        grabbers\grabbers::grabber('test.no');
    }
}
