<?php


namespace datagutten\xmltv\grabbers;


use InvalidArgumentException;

class grabbers
{
    public static $grabbers = [
        'natgeo.no' => natgeo::class,
        'disneychannel.no' => disney_channel::class,
        'xd.disneychannel.no' => disney_xd::class,
        'junior.disneychannel.no' => disney_junior::class];

    /**
     * @param string $xmltv_id XMLTV DNS-like id
     * @return common
     */
    public static function grabber($xmltv_id)
    {
        if(isset(self::$grabbers[$xmltv_id]))
            return self::$grabbers[$xmltv_id];
        else
            throw new InvalidArgumentException('No grabber for '.$xmltv_id);
    }

    /**
     * @return array
     */
    public static function getGrabbers()
    {
        return self::$grabbers;
    }
}
