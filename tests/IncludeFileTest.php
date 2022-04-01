<?php

namespace Civi\CompilePlugin\Tests;

use ProcessHelper\ProcessHelper as PH;

/**
 * Class IncludeFileTest
 * @package Civi\CompilePlugin\Tests
 *
 * This is general integration test of the plugin. It creates an example project which uses the
 * current/under-development plugin. The project relies on a `compile-includes` directive.
 */
class IncludeFileTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        return parent::getComposerJson() + [
          'name' => 'test/include-file-test',
          'require' => [
              'civicrm/composer-compile-plugin' => '@dev',
              'test/strawberry-jam' => '@dev',
          ],
          'minimum-stability' => 'dev',
        ];
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
     * When running 'composer install', it should generate the 'jam.in'.
     */
    public function testComposerInstall()
    {
        $this->assertFileNotExists('vendor/test/strawberry-jam/subordinate/jam.out');

        PH::runOk('COMPOSER_COMPILE=1 composer install -v');

        $this->assertFileContent('vendor/test/strawberry-jam/subordinate/jam.out', "STRAWBERRY-FIELDS\n");
    }

    protected static function resetCompileFiles()
    {
        self::cleanFile('vendor/test/strawberry-jam/subordinate/jam.out');
        $defaultFiles = [
            'vendor/test/strawberry-jam/subordinate/jam.in' => "strawberry-fields\n",
        ];
        foreach ($defaultFiles as $file => $content) {
            // If the package hasn't been installed yet, then there's nothing to clear.
            if (file_exists(dirname($file))) {
                file_put_contents($file, $content);
            }
        }
    }
}
