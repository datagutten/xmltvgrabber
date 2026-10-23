<?php
$config['xmltv_path'] = getenv('XMLTV_PATH') ?: '/xmltv';
$config['xmltv_sub_folder'] = getenv('XMLTV_SUB_FOLDER') ?: 'xmltv_php'; //Sub folder in the channel folder

return $config;