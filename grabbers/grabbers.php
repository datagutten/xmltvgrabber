<?php


namespace datagutten\xmltv\grabbers;


use FilesystemIterator;
use InvalidArgumentException;
use SplFileInfo;

class grabbers
{
    /**
     * @var base\common[]
     */
    public static array $grabbers = [
        'disneychannel.no' => disney_channel::class,
        'junior.disneychannel.no' => disney_junior::class,
        'tvnorge.no' => discovery_tvnorge::class,
        'fem.no' => discovery_fem_no::class,
        'voxtv.no' => discovery_vox_no::class,
        'max.no' => discovery_max_no::class,
        'discovery.no' => discovery_channel_no::class,
        'tlc.discovery.no'=> discovery_tlc_no::class,
        'investigation.discovery.no' => discovery_investigation_no::class,
        'science.discovery.no' => discovery_science_no::class,
        'animalplanet.discovery.no' => discovery_animalplanet_no::class,
        'nrk1.nrk.no' => nrk1::class,
        'nrk2.nrk.no' => nrk2::class,
        'nrk3.nrk.no' => nrk3::class,
        'cbsreality.com' => cbsreality::class,
        'nrksuper.nrk.no' => nrksuper::class,
        'tv2.no' => tv2::class,
        'zebra.tv2.no' => tv2_zebra::class,
        'nickelodeon.no' => nickelodeon_no::class,
        'boomerangtv.no' => boomerang::class,
        ];

    /**
     * Get grabber class
     * @param string $xmltv_id XMLTV DNS-like id
     * @return base\common Class name of grabber class
     */
    public static function grabber(string $xmltv_id)
    {
        $grabbers = self::getGrabbers();
        if (isset($grabbers[$xmltv_id]))
            return $grabbers[$xmltv_id];
        else
            throw new InvalidArgumentException('No grabber for ' . $xmltv_id);
    }

    /**
     * @return base\common[]
     */
    public static function getGrabbers(): array
    {
        $grabbers = self::$grabbers;
        $iterator = new FilesystemIterator(__DIR__, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);

        /**
         * @var $fileInfo SplFileInfo
         */
        foreach ($iterator as $fileInfo)
        {
            if ($fileInfo->getExtension() != 'php')
                continue;
            if ($fileInfo->isDir())
                continue;
            if ($fileInfo->getBasename() == 'grabbers.php')
                continue;

            /** @var base\common $class */
            $class = 'datagutten\\xmltv\\grabbers\\' . $fileInfo->getBasename('.php');
            if (property_exists($class, 'xmltv_id') && !empty($class::$xmltv_id))
            {
                $id = $class::$xmltv_id;
                if (!isset($grabbers[$id]))
                    $grabbers[$id] = $class;
            }
        }
        return $grabbers;
    }
}
