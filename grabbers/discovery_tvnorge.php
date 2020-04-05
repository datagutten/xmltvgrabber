<?php


namespace datagutten\xmltv\grabbers;


class discovery_tvnorge extends discovery_no
{
    function __construct()
    {
        parent::__construct('tvnorge.no', 'nb');
    }
}