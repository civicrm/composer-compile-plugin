<?php

namespace Civi\CompilePlugin\Tests;

use Composer\Plugin\PluginInterface;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class ScssPhpMethodTest
 * @package Civi\CompilePlugin\Tests
 *
 * This is general integration test of the plugin. It uses a 'php-method'
 * to compile some SCSS.
 */
class ScssPhpMethodTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        return parent::getComposerJson() + [
            'name' => 'test/patch-test',
            'require' => [
                'test/gnocchi' => '@dev',
            ],
            'minimum-stability' => 'dev',
        ];
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::initTestProject(static::getComposerJson());
    }

    /**
     * When running 'composer install', it should generate gnocchi's "build.css".
     */
    public function testComposerInstall()
    {
        $this->assertFileNotExists('vendor/test/gnocchi/build.css');

        PH::runOk('COMPOSER_COMPILE=1 composer install -v');

        $normalize = function ($s) {
            return trim(preg_replace(';\s+;', ' ', $s));
        };
        $actual = $normalize(file_get_contents('vendor/test/gnocchi/build.css'));
        $expected = $normalize(file_get_contents('vendor/test/gnocchi/build.css-expected'));
        $this->assertNotEmpty($expected);
        $this->assertEquals($expected, $actual);
    }
}
