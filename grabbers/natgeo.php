<?Php
namespace  datagutten\xmltv\grabbers;
use datagutten\xmltv\tools\build\programme;
use DOMDocument;
use DOMElement;

class natgeo extends base\common
{
    function __construct()
    {
        parent::__construct('natgeo.no', 'nb');
    }

    function grab($timestamp=null)
    {
        if(empty($timestamp))
            $timestamp = strtotime('midnight');

        list($day_start, $day_end) = self::day_start_end($timestamp);

        $dom=new DOMDocument;

        $url = sprintf('http://www.natgeotv.com/no/tvguide/natgeo/%s', date('Ymd',$timestamp));
        $data = $this->download_cache($url, $timestamp);

        @$dom->loadHTML($data);
        $days = $dom->getElementById('acilia-schedule-list'); //div

        /**
         * @var $day DOMElement section tag
         */
        foreach ($days->childNodes as $day) {
            $programs = $day->getElementsByTagName('li');
            /**
             * @var $program DOMElement
             */
            foreach ($programs as $program) {
                $program_start = $program->getAttribute('data-datetime-timestamp');
                $program_end = $program->getAttribute('data-end-timestamp');
                if($program_start<$day_start)
                    continue;
                if($program_start>$day_end)
                    break 2;

                $programme = new programme($program_start, $this->tv);
                $programme->stop($program_end);

                $title = $program->getelementsbytagname('h3')->item(0)->textContent;
                $programme->title($title);

                $description = $program->getelementsbytagname('p')->item(0)->textContent;


                if ($program->getelementsbytagname('h4')->length == 1) {
                    $epname = $program->getelementsbytagname('h4')->item(0)->textContent;

                    if (preg_match('/(.*),\s+(Sesong ([0-9]+).+Episode ([0-9]+))/', $epname, $ep)) {
                        $programme->sub_title($ep[1]); //Episode title
                        $programme->description($description);
                        $programme->series($ep[4], $ep[3]);
                        $programme->onscreen($ep[2]);
                    } else {
                        $programme->sub_title(trim($epname));
                        $programme->description($description);
                    }
                } else {
                    $programme->description($description);
                }
            }
        }
        return $this->save_file($timestamp);
    }
}