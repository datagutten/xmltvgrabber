<?php

namespace datagutten\xmltv\tests\grabbers;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

abstract class grabberTestCase extends TestCase
{
    /**
     * @var Filesystem
     */
    public Filesystem $filesystem;

    public function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tearDown();
        $config = file_get_contents(__DIR__ . '/test_config.php');
        $config = str_replace('__DIR__', __DIR__, $config);
        file_put_contents(__DIR__ . '/config.php', $config);
        set_include_path(__DIR__);
        $this->filesystem->mkdir(__DIR__ . '/xmltv_test');
    }

    public function tearDown(): void
    {
        $this->filesystem->remove(__DIR__ . '/xmltv_test');
        $this->filesystem->remove(__DIR__ . '/config.php');
    }
}