<?php


namespace datagutten\xmltv\grabbers;


use FilesystemIterator;
use InvalidArgumentException;
use SplFileInfo;

class grabbers
{
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
            if (!empty($class::$folder_suffix))
            {
                $id = $class::$xmltv_id . '_' . $class::$folder_suffix;
                if (!isset($grabbers[$id]))
                    $grabbers[$id] = $class;
            }
            elseif (property_exists($class, 'xmltv_id') && !empty($class::$xmltv_id))
            {
                $id = $class::$xmltv_id;
                if (!isset($grabbers[$id]))
                    $grabbers[$id] = $class;
            }
        }
        return $grabbers;
    }
}
