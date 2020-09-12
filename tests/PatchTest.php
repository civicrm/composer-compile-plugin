<?php

namespace Civi\CompilePlugin\Tests;

use Composer\Plugin\PluginInterface;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class PatchTest
 * @package Civi\CompilePlugin\Tests
 *
 * This is general integration test of the plugin. It runs composer.json with
 * a combination of composer-compile-plugin and composer-patches.
 *
 * Ensure that `extra.patches` are applied before `extra.compile` tasks.
 */
class PatchTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        $r = parent::getComposerJson() + [
            'name' => 'test/patch-test',
            'require' => [
                'civicrm/composer-compile-plugin' => '@dev',
                'test/cherry-jam' => '@dev',
                'cweagans/composer-patches' => '*',
            ],
            'minimum-stability' => 'dev',
            'extra' => [
                'patches' => [
                    'test/cherry-jam' => [
                        'Some hackery' => 'cherry-jam-hackery.diff'
                    ],
                ],
            ],
        ];

        // For this test, we'll need to patch. We want a copy (not symlink) so we can hack at it.
        if (!is_array($r['repositories']['test-cherry-jam'])) {
            throw new \RuntimeException("Inconsistency in composer.json");
        }
        $r['repositories']['test-cherry-jam']['options'] = [
            'symlink' => false,
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
   * When running 'composer install', it should generate 'jam.out' with suitable patches in place.
   */
    public function testComposerInstall()
    {
        $this->assertFileNotExists('vendor/test/cherry-jam/jam.out');

        PH::runOk('COMPOSER_COMPILE=1 composer install -v');

        $this->assertFileContent('vendor/test/cherry-jam/jam.out', "patches in the house\n");
    }
}
