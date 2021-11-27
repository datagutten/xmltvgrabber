<?php


namespace datagutten\xmltv\grabbers;


class disney_channel extends base\disney_no
{
    public static $channel_id = 'disneychannel.no';
    public static $language = 'nb';
    function __construct()
    {
        parent::__construct('disneychannel.no', 'nb');
    }
}