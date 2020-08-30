<?php


namespace datagutten\xmltv\grabbers;


use InvalidArgumentException;

class grabbers
{
    /**
     * @var base\common[]
     */
    public static $grabbers = [
        'natgeo.no' => natgeo::class,
        'disneychannel.no' => disney_channel::class,
        'xd.disneychannel.no' => disney_xd::class,
        'tvnorge.no' => discovery_tvnorge::class,
        'fem.no' => discovery_fem_no::class,
        'voxtv.no' => discovery_vox_no::class,
        'max.no' => discovery_max_no::class,
        'discovery.no' => discovery_channel_no::class,
        'tlc.discovery.no'=> discovery_tlc_no::class,
        'investigation.discovery.no' => discovery_investigation_no::class,
        'science.discovery.no' => discovery_science_no::class,
        'animalplanet.discovery.no' => discovery_animalplanet_no::class,
        'eurosport.no' => discovery_eurosport_no::class,
        'world.discovery.no' => discovery_world_no::class,
        'hd.discovery.no' => discovery_hd_no::class,
        'nrk1.nrk.no' => nrk1::class,
        'nrk2.nrk.no' => nrk2::class,
        'nrk3.nrk.no' => nrk3::class,
        'nrksuper.nrk.no' => nrksuper::class,
        ];

    /**
     * @param string $xmltv_id XMLTV DNS-like id
     * @return base\common
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
