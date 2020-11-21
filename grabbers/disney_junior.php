<?php


namespace datagutten\xmltv\grabbers;


class disney_junior extends base\disney_no
{
    function __construct()
    {
        parent::__construct('junior.disneychannel.no', 'nb');
    }
}