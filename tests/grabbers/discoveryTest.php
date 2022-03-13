<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */

namespace datagutten\xmltv\tests\grabbers;

use datagutten\xmltv\grabbers;

class discovery_no_slug extends grabbers\base\discovery_no
{
    public static string $xmltv_id = 'test.discovery.no';
}

class discoveryTest extends grabberTestCase
{
    public function testNoSlug()
    {
        $grabber = new discovery_no_slug();
        $this->expectException(grabbers\exceptions\GrabberException::class);
        $this->expectExceptionMessage('Channel id not defined in grabber datagutten\xmltv\tests\grabbers\discovery_no_slug');
        $grabber->grab();
    }
}
