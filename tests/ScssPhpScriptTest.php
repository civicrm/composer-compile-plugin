<?php

namespace Civi\CompilePlugin\Tests;

use Composer\Plugin\PluginInterface;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class ScssPhpScriptTest
 * @package Civi\CompilePlugin\Tests
 *
 * This is general integration test of the plugin. It uses a 'php-script'
 * to compile some SCSS.
 */
class ScssPhpScriptTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        return parent::getComposerJson() + [
            'name' => 'test/patch-test',
            'require' => [
                'test/scss-script' => '@dev',
            ],
            'minimum-stability' => 'dev',
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::initTestProject(static::getComposerJson());
    }

    /**
     * When running 'composer install', it should generate scss-script's "build.css".
     */
    public function testComposerInstall()
    {
        $this->assertFileNotExists('vendor/test/scss-script/build.css');

        PH::runOk('COMPOSER_COMPILE=1 composer install -v');

        $this->assertSameCssFile('vendor/test/scss-script/build.css-expected', 'vendor/test/scss-script/build.css');
    }
}
