<?php

namespace Civi\CompilePlugin\Tests;

use Civi\CompilePlugin\Event\CompileEvents;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class SelfReferenceTest
 * @package Civi\CompilePlugin\Tests
 *
 * If a test package requires itself indirectly, then it might throw-off the PackageSorter.
 */
class SelfReferenceTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        return parent::getComposerJson() + [
          'name' => 'test/self-reference',
          'require' => [
              'civicrm/composer-compile-plugin' => '@dev',
              'test/crypto-reference' => '*',
          ],
          'replace' => [
              'test/crypto-reference' => 'self.version',
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
    }

    public static function setUpBeforeClass()
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
