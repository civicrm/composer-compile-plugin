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
            'name' => 'test/scss-php-method-test',
            'require' => [
                'test/scss-method' => '@dev',
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
     * When running 'composer install', it should generate scss-method's "build.css".
     */
    public function testComposerInstall()
    {
        $this->assertFileNotExists('vendor/test/scss-method/build.css');

        PH::runOk('COMPOSER_COMPILE=1 composer install -v');

        $this->assertSameCssFile('vendor/test/scss-method/build.css-expected', 'vendor/test/scss-method/build.css');
    }
}
