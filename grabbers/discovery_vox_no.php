<?php


namespace datagutten\xmltv\grabbers;


class discovery_vox_no extends discovery_no
{
    function __construct()
    {
        parent::__construct('voxtv.no', 'nb');
    }
}