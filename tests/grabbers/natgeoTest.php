<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */

namespace datagutten\xmltv\tests\grabbers;

use datagutten\xmltv\grabbers;

class NatGeoNoSlug extends grabbers\base\natgeo
{
    public static string $xmltv_id = 'test.natgeo.no';
    public static string $language = 'nb';
}

class NatGeoInvalidSlug extends grabbers\base\natgeo
{
    public static string $xmltv_id = 'test.natgeo.no';
    public static string $slug = 'foo';
    public static string $language = 'nb';
}

class NatGeoInvalidLanguage extends grabbers\base\natgeo
{
    public static string $xmltv_id = 'test.natgeo.no';
    public static string $slug = 'foo';
    public static string $language = 'bad';
}

class natgeoTest extends grabberTestCase
{
    public function testNoSlug()
    {
        $grabber = new NatGeoNoSlug();
        $this->expectException(grabbers\exceptions\GrabberException::class);
        $this->expectExceptionMessage('Channel slug not defined in grabber datagutten\xmltv\tests\grabbers\NatGeoNoSlug');
        $grabber->grab();
    }

    public function testInvalidSlug()
    {
        $grabber = new NatGeoInvalidSlug();
        $this->expectException(grabbers\exceptions\GrabberException::class);
        $this->expectExceptionMessage('404 Not Found');
        $grabber->grab();
    }

    public function testInvalidLanguage()
    {
        $grabber = new NatGeoInvalidLanguage();
        $this->expectException(grabbers\exceptions\GrabberException::class);
        $this->expectExceptionMessage('Invalid language "bad" in grabber datagutten\xmltv\tests\grabbers\NatGeoInvalidLanguage');
        $grabber->grab();
    }
}
