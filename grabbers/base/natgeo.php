<?Php
namespace datagutten\xmltv\grabbers\base;
use datagutten\xmltv\grabbers\exceptions;
use datagutten\xmltv\tools\build\programme;
use DOMDocument;
use DOMElement;

abstract class natgeo extends common
{
    /**
     * @var string Natgeo channel slug
     */
    public static string $slug;
    public static array $language_paths = ['nb' => 'no/tvguide', 'da' => 'dk/programoversigt', 'sv' => 'se/tabla'];

    function grab($timestamp=null)
    {
        if(empty(static::$slug))
            throw new exceptions\GrabberException(sprintf('Channel slug not defined in grabber %s', static::class));

        if(empty($timestamp))
            $timestamp = strtotime('midnight');

        list($day_start, $day_end) = self::day_start_end($timestamp);

        $dom=new DOMDocument;

        if (array_key_exists(static::$language, static::$language_paths))
            $path = static::$language_paths[static::$language];
        else
            throw new exceptions\GrabberException(sprintf('Invalid language "%s" in grabber %s', static::$language, static::class));

        $url = sprintf('https://www.natgeotv.com/%s/%s/%s', $path, static::$slug, date('Ymd', $timestamp));
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