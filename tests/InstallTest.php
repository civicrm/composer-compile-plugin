<?php

namespace Civi\CompilePlugin\Tests;

use ProcessHelper\ProcessHelper as PH;

/**
 * Class SniffTest
 * @package Civi\CompilePlugin\Tests
 *
 * This is general integration test of the plugin. It creates an example project which uses the
 * current/under-development plugin. The 'composer install' command should perform compilation.
 */
class InstallTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        return parent::getComposerJson() + [
          'name' => 'test/sniff-test',
          'require' => [
            'civicrm/composer-compile-plugin' => '@dev',
          ],
          'minimum-stability' => 'dev',
          'extra' => [
            'compile' => [
              [
                'tag' => ['fondue'],
                'title' => 'Compile fondue.out from fondue.in',
                'command' => 'echo START > fondue.out; cat fondue.in >> fondue.out; echo END >> fondue.out',
                  // TODO 'watch' => ['fondue.in'],
              ]
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
     * When running 'composer install', it should generate the 'fondue.out' file.
     */
    public function testComposerInstall()
    {
        $this->assertFileNotExists('fondue.in');
        $this->assertFileNotExists('fondue.out');

        file_put_contents('fondue.in', "gouda\n");
        PH::runOk('composer install -v');

        $this->assertFileContent('fondue.out', "START\ngouda\nEND\n");
    }

}