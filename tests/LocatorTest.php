<?php

namespace Civi\CompilePlugin\Tests;

use Composer\Plugin\PluginInterface;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class LocatorTest
 * @package Civi\CompilePlugin\Tests
 *
 * This is general integration test of the plugin. It runs composer.json with
 * a combination of composer-compile-plugin and composer-locator.
 *
 * Ensure that the package map is available at compile time.
 */
class LocatorTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        $r = parent::getComposerJson() + [
            'name' => 'test/locator-test',
            'require' => [
                'civicrm/composer-compile-plugin' => '@dev',
                'test/cherry-jam' => '@dev',
                'mindplay/composer-locator' => '*',
            ],
            'minimum-stability' => 'dev',
            'extra' => [
                'compile' => [
                    ['run' => '@php-eval echo "LOCATE JAM:" . ComposerLocator::getPath("test/cherry-jam") . "\n";']
                ],
            ],
        ];
        return $r;
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::initTestProject(static::getComposerJson());
        file_put_contents(
            self::getTestDir() . '/cherry-jam-hackery.diff',
            file_get_contents(self::getPluginSourceDir() . '/tests/pkgs/cherry-jam-hackery.diff')
        );
    }

    /**
     * When running 'composer install', the compilation task should have access to the ComposerLocator.
     */
    public function testComposerInstall()
    {
        $version = PH::runOk('composer --version')->getOutput();
        if (!preg_match(';version 1;', $version)) {
            $this->markTestSkipped('Cannot test mindplay/composer-locator on composer v2. It does not yet support v2.');
        }

        $this->assertFileNotExists('vendor/test/cherry-jam/jam.out');

        $p = PH::runOk('COMPOSER_COMPILE=1 composer install -v');

        $matches = preg_grep(';^LOCATE JAM:;', explode("\n", $p->getOutput()));
        $this->assertCount(1, $matches);
        $parts = explode(':', array_shift($matches));
        $this->assertNotEmpty($parts[1]);
        $this->assertEquals(realpath(self::getTestDir() . '/vendor/test/cherry-jam'), realpath($parts[1]));
    }
}
