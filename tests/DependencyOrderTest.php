<?php

namespace Civi\CompilePlugin\Tests;

use Civi\CompilePlugin\Event\CompileEvents;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class DependencyOrderTest
 * @package Civi\CompilePlugin\Tests
 *
 * Test that checks that packages are sorted in a way that Dependant
 * tasks are run first
 */
class DependencyOrderTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        $composer_json = parent::getComposerJson();
        
        if (empty($composer_json['repositories'])) {
            $composer_json['repositories'] = [];
        }
        $composer_json['repositories']['test-parent'] = [
            'type' => 'path',
            'url' => self::getPluginSourceDir() . '/tests/pkgs/parent',
        ];
        $composer_json['repositories']['test-child'] = [
            'type' => 'path',
              'url' => self::getPluginSourceDir() . '/tests/pkgs/child',
        ];


        $returning =  $composer_json + [
          'name' => 'test/dependency-order-test',
          'require' => [
              'civicrm/composer-compile-plugin' => '@dev',
              'test/parent' => '*',
              'test/child' => '*',
          ],
          'minimum-stability' => 'dev',
          'extra' => [
              'compile' => [
                   [
                      'title' => 'Compile first',
                      'shell' => 'echo MARK: RUN FIRST',
                   ],
              ],
          ],
        ];
        return $returning;
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::initTestProject(static::getComposerJson());
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::resetCompileFiles();
    }

    protected function tearDown(): void
    {
        self::resetCompileFiles();
        parent::tearDown();
    }


    /**
     * When running 'composer install', it runs various events.
     */
    public function testComposerInstall()
    {
        $this->assertFileNotExists(self::getPluginSourceDir() . '/tests/pkgs/parent/parent.out');
        $this->assertFileNotExists(self::getPluginSourceDir() . '/tests/pkgs/child/child.out');
        
        $p = PH::runOk('COMPOSER_COMPILE=1 composer install');

        $this->assertFileExists(self::getPluginSourceDir() . '/tests/pkgs/parent/parent.out');
        $this->assertFileExists(self::getPluginSourceDir() . '/tests/pkgs/child/child.out');
    }

    protected static function resetCompileFiles()
    {
        self::cleanFile(self::getPluginSourceDir() . '/tests/pkgs/parent/parent.out');
        self::cleanFile(self::getPluginSourceDir() . '/tests/pkgs/child/child.out');
    }
}
