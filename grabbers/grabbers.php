<?php


namespace datagutten\xmltv\grabbers;


use InvalidArgumentException;

class grabbers
{
    public static $grabbers = [
        'natgeo.no' => natgeo::class,
        'disneychannel.no' => disney_channel::class,
        'xd.disneychannel.no' => disney_xd::class,
        'junior.disneychannel.no' => disney_junior::class,
        'tvnorge.no' => discovery_tvnorge::class,
        'fem.no' => discovery_fem_no::class,
        'voxtv.no' => discovery_vox_no::class,
        'max.no' => discovery_max_no::class,
        'tlc.discovery.no'=> discovery_tlc_no::class,
        'investigation.discovery.no' => discovery_investigation_no::class,
        ];

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
