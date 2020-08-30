<?php


namespace datagutten\xmltv\grabbers;


class disney_channel extends base\disney_no
{
    function __construct()
    {
        parent::__construct('disneychannel.no', 'nb');
    }
}