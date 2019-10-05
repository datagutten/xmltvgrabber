<?Php
namespace  datagutten\xmltv\grabbers;
use datagutten\xmltv\tools\build\programme;
use datagutten\xmltv\tools\build\tv;
use DOMDocument;
use DOMElement;

class natgeo extends common
{
    function __construct()
    {
        parent::__construct('natgeo.no', 'nb');
    }

    function grab($timestamp=null)
    {
        if(empty($timestamp))
            $timestamp = strtotime('midnight');

        $dom=new DOMDocument;

        $url = sprintf('http://www.natgeotv.com/no/tvguide/natgeo/%s', date('Ymd',$timestamp));
        $data = $this->download($url, $timestamp);
        @$dom->loadHTML($data);
        $days = $dom->getElementById('scheduleDays');

        $ul = $dom->getelementsbytagname('ul');
        foreach ($ul as $day) {
            if ($day->childNodes->length < 5)
                continue;
            if (substr($day->childNodes->item(0)->textContent, 0, 2) != 'N' . chr(0xc3))
                continue;

            /**
             * @var $program DOMElement
             */
            foreach ($day->childNodes as $program) {
                $date = $program->getattribute('data-datetime-date'); //Program date
                if (isset($prev_date) && isset($start_time_stamp) && $date != $prev_date) //New day
                {
                    $filename = $this->tv->save_file($start_time_stamp); //Save previous day
                    echo $filename . "\n";
                    //Restart xmltv
                    $this->tv->init_xml();
                }

                $start_time = $program->getelementsbytagname('h5')->item(0)->textContent; //Program start time
                $start_time_stamp = strtotime($date . ' ' . $start_time);

                $programme = new programme($start_time_stamp, $this->tv);

                $title = $program->getelementsbytagname('h3')->item(0)->textContent;
                $programme->title($title);

                $description = $program->getelementsbytagname('p')->item(0)->textContent;


                if ($program->getelementsbytagname('h4')->length == 1) {
                    $epname = $program->getelementsbytagname('h4')->item(0)->textContent;

                    if (preg_match('/(.*),\s+(Sesong ([0-9]+).+Episode ([0-9]+))/', $epname, $ep)) {
                        $programme->sub_title($ep[1]);
                        $programme->description($description);
                        $programme->series($ep[4], $ep[3]);
                        $programme->onscreen($ep[2]);
                    } else {
                        $programme->sub_title($epname);
                        $programme->description($description);
                    }
                } else
                    $programme->description($description);

                $prev_date = $date; //Save current date for next iteration
                //print_r($programme->xml);
                //break 2;
            }
        }
    }
}