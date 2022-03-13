<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */

namespace datagutten\xmltv\tests\grabbers;

use datagutten\xmltv\grabbers;
use PHPUnit\Framework\TestCase;

class tv2_no_slug extends grabbers\base\tv2no
{
    public static string $xmltv_id = 'test.tv2.no';
}

class tv2_bad_slug extends grabbers\base\tv2no
{
    public static string $xmltv_id = 'test.tv2.no';
    public static string $slug = 'foo';
}

class tv2noTest extends TestCase
{
    public function testNoSlug()
    {
        $grabber = new tv2_no_slug();
        $this->expectException(grabbers\exceptions\GrabberException::class);
        $this->expectExceptionMessage('Channel slug not defined in grabber datagutten\xmltv\tests\grabbers\tv2_no_slug');
        $grabber->grab();
    }

    public function testInvalidSlug()
    {
        $grabber = new tv2_bad_slug();
        $this->expectException(grabbers\exceptions\GrabberException::class);
        $this->expectExceptionMessage('Unknown channel slug "foo" in grabber datagutten\xmltv\tests\grabbers\tv2_bad_slug');
        $grabber->grab();
    }
}
