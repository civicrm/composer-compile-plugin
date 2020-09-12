<?php

namespace Civi\CompilePlugin\Tests;

use ProcessHelper\ProcessHelper as PH;

/**
 * Class SniffTest
 * @package Civi\CompilePlugin\Tests
 *
 * This is general integration test of the plugin. It creates an example project which uses the
 * current/under-development plugin. The 'composer compile' command should perform compilation.
 *
 * This test project has compiled assets from these places:
 * 1. The root project (asset 'fondue.out')
 * 2. The 'cherry-yogurt' project (asset 'yogurt.out')
 * 3. The 'cherry-jam' project (asset 'jam.out')
 */
class CompileCommandTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        return parent::getComposerJson() + [
          'name' => 'test/sniff-test',
          'require' => [
              'civicrm/composer-compile-plugin' => '@dev',
              'test/cherry-jam' => '@dev',
              'test/cherry-yogurt' => '@dev',
          ],
          'minimum-stability' => 'dev',
          'extra' => [
            'compile' => [
              [
                  'tag' => ['fondue'],
                  'title' => 'Compile <comment>fondue.out</comment> from <comment>fondue.in</comment>',
                  'shell' => 'echo START > fondue.out; cat fondue.in >> fondue.out; echo END >> fondue.out',
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

    protected function setUp()
    {
        parent::setUp();
        self::resetCompileFiles();
    }

    protected function tearDown()
    {
        self::resetCompileFiles();
        parent::tearDown();
    }

    /**
     * When running 'composer install', it should generate the 'fondue.out' and 'yogurt.out' files.
     */
    public function testComposerInstall()
    {
        $this->assertFileNotExists('fondue.out');

        PH::runOk('COMPOSER_COMPILE=1 composer install -v');

        $this->assertFileContent('fondue.out', "START\ngouda\nEND\n");
        $this->assertFileContent('vendor/test/cherry-jam/jam.out', "RAINIER-CHERRY\n");
        $this->assertFileContent('vendor/test/cherry-yogurt/yogurt.out', "START\nmilk\nRAINIER-CHERRY\nEND\n");
    }

    /**
     * When running 'composer compile', it should generate the 'fondue.out' and 'yogurt.out' files.
     */
    public function testComposerCompile()
    {
        $this->assertFileNotExists('fondue.out');

        // We need to make sure the project is setup.
        PH::runOk('COMPOSER_COMPILE=0 composer install -v');
        $this->assertFileNotExists('fondue.out');

        // First pass at compilation in a clean-ish environment
        PH::runOk('COMPOSER_COMPILE=1 composer compile -v');

        $this->assertFileContent('fondue.out', "START\ngouda\nEND\n");
        $this->assertFileContent('vendor/test/cherry-jam/jam.out', "RAINIER-CHERRY\n");
        $this->assertFileContent('vendor/test/cherry-yogurt/yogurt.out', "START\nmilk\nRAINIER-CHERRY\nEND\n");

        // Second pass at compilation with modified content
        file_put_contents('fondue.in', "gruyere\ngouda\n");
        file_put_contents('vendor/test/cherry-jam/jam.in', "bing-cherry\n");
        file_put_contents('vendor/test/cherry-yogurt/yogurt.in', "milk\nstreptococcus thermophilus\n");
        PH::runOk('COMPOSER_COMPILE=1 composer compile -v');

        $this->assertFileContent('fondue.out', "START\ngruyere\ngouda\nEND\n");
        $this->assertFileContent('vendor/test/cherry-jam/jam.out', "BING-CHERRY\n");
        $this->assertFileContent('vendor/test/cherry-yogurt/yogurt.out', "START\nmilk\nstreptococcus thermophilus\nBING-CHERRY\nEND\n");
    }

    /**
     * @param string $inputFilterExpr
     *   The value to send to 'composer compile <FILTER-EXPR>'
     * @param string $expectFile
     *   The file created by the compilation task.
     * @param string $expectFileContent
     *   The content of the file created by the compilation task.
     * @dataProvider getExampleIds
     */
    public function testComposerCompileById($inputFilterExpr, $expectFile, $expectFileContent)
    {
        $allFiles = ['fondue.out', 'vendor/test/cherry-jam/jam.out', 'vendor/test/cherry-yogurt/yogurt.out'];
        $otherFiles = array_diff($allFiles, [$expectFile]);

        $assertAll = function ($method, $args) {
            foreach ($args as $arg) {
                $this->$method($arg);
            }
        };

        // We need to make sure the project is setup.
        $assertAll('assertFileNotExists', $allFiles);
        PH::runOk('COMPOSER_COMPILE=0 composer install -v');
        $assertAll('assertFileNotExists', $allFiles);

        PH::runOk('composer compile ' . escapeshellarg($inputFilterExpr));
        $this->assertFileContent($expectFile, $expectFileContent);
        $assertAll('assertFileNotExists', $otherFiles);
    }

    public function getExampleIds()
    {
        $es = [];
        $es['test/cherry-jam:1'] = [
          'test/cherry-jam:1',
          'vendor/test/cherry-jam/jam.out',
          "RAINIER-CHERRY\n",
        ];
        $es['test/cherry-yogurt'] = [
          'test/sniff-test',
          'fondue.out',
          "START\ngouda\nEND\n",
        ];
        return $es;
    }

    protected static function resetCompileFiles()
    {
        self::cleanFile('fondue.out');
        self::cleanFile('vendor/test/cherry-jam/jam.out');
        self::cleanFile('vendor/test/cherry-yogurt/yogurt.out');
        $defaultFiles = [
            './fondue.in' => "gouda\n",
            'vendor/test/cherry-jam/jam.in' => "rainier-cherry\n",
            'vendor/test/cherry-yogurt/yogurt.in' => "milk\n",
        ];
        foreach ($defaultFiles as $file => $content) {
            // If the package hasn't been installed yet, then there's nothing to clear.
            if (file_exists(dirname($file))) {
                file_put_contents($file, $content);
            }
        }
    }
}
