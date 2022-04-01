<?php

namespace Civi\CompilePlugin\Tests;

use Civi\CompilePlugin\Event\CompileEvents;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class CircularReferenceTest
 * @package Civi\CompilePlugin\Tests
 *
 * If there is a circular dependency graph in a test package, then it might throw-off the PackageSorter.
 */
class CircularReferenceTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        $composer_json = parent::getComposerJson();
        if (empty($composer_json['repositories'])) {
            $composer_json['repositories'] = [];
        }

        $composer_json['repositories']['test-chicken'] = [
              'type' => 'path',
              'url' => self::getPluginSourceDir() . '/tests/pkgs/chicken',
        ];
        $composer_json['repositories']['test-egg'] = [
              'type' => 'path',
              'url' => self::getPluginSourceDir() . '/tests/pkgs/egg',
        ];

        $returning =  $composer_json + [
          'name' => 'test/circular-reference',
          'require' => [
              'civicrm/composer-compile-plugin' => '@dev',
              'test/chicken' => '*',
              'test/egg' => '*',
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

    /**
     * When running 'composer install', it run various events.
     */
    public function testComposerInstall()
    {
        $p = PH::runOk('COMPOSER_COMPILE_PASSTHRU=always COMPOSER_COMPILE=1 composer install');
        $expectLines = [
            "^MARK: RUN FIRST",
        ];
        $this->assertOutputLines($expectLines, ';^MARK:;', $p->getOutput());
    }
}
