<?php


namespace datagutten\xmltv\grabbers;


class discovery_tvnorge extends base\discovery_no
{
    function __construct()
    {
        parent::__construct('tvnorge.no', 'nb');
    }
}