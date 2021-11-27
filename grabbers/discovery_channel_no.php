<?php


namespace datagutten\xmltv\grabbers;


class discovery_channel_no extends base\tv2no
{
    function __construct()
    {
        parent::__construct('discovery.no', 'nb');
    }
}