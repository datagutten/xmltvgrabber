<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */

namespace datagutten\xmltv\tests\grabbers;


use datagutten\tools\files\files as file_tools;
use datagutten\xmltv\grabbers\base\common;
use datagutten\xmltv\grabbers\exceptions\XMLTVError;
use datagutten\xmltv\tools\build\programme;


class AlternateChannel extends common
{
    public static string $xmltv_id = 'test.no';
    public static string $language = 'nb';
    public static string $folder_suffix = 'test_suffix';

    /**
     * Dummy method to call file with create=false to get coverage on exception handler
     * @param int $timestamp
     * @param string $extension
     * @return string
     * @throws XMLTVError
     */
    public function local_file_no_create(int $timestamp, string $extension = 'html')
    {
        return $this->file(static::$xmltv_id, $timestamp, 'raw_data', $extension);
    }

    public function grab(int $timestamp = 0): ?string
    {
        return $timestamp; //Dummy method to avoid error
    }
}

class grabberAlternateSubFolderTest extends grabberTestCase
{
    public function testLocalFileSuffix()
    {
        $grabber = new AlternateChannel();
        $file = $grabber->local_file(strtotime('2023-02-01'));
        $expected_file = file_tools::path_join($grabber->files->xmltv_path, 'test.no', 'raw_data_test_suffix', '2023', 'test.no_2023-02-01.html');
        $this->assertEquals($expected_file, $file);
    }

    public function testSaveFile()
    {
        $grabber = new AlternateChannel();
        new programme(strtotime('2023-02-01 12:00'), $grabber->tv);

        $file = $grabber->save_file(strtotime('2023-02-01'));
        $expected_file = file_tools::path_join($grabber->files->xmltv_path, 'test.no', 'xmltv_test_test_suffix', '2023', 'test.no_2023-02-01.xml');
        $this->assertEquals($expected_file, $file);
    }
}