<?php


namespace datagutten\xmltv\grabbers;


class disney_xd extends base\disney_no
{
    function __construct()
    {
        parent::__construct('xd.disneychannel.no', 'nb');
    }
}