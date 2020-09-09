<?php

namespace Civi\CompilePlugin\Tests;

use Civi\CompilePlugin\Event\CompileEvents;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class EventTest
 * @package Civi\CompilePlugin\Tests
 *
 * This is general integration test of the plugin. It creates an example project which uses the
 * current/under-development plugin.  It asserts that various events fire.
 */
class EventTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        return parent::getComposerJson() + [
          'name' => 'test/event-test',
          'require' => [
              'civicrm/composer-compile-plugin' => '@dev',
          ],
          'minimum-stability' => 'dev',
          'scripts' => [
            CompileEvents::PRE_COMPILE_LIST => 'echo MARK: PRE_COMPILE_LIST',
            CompileEvents::POST_COMPILE_LIST => 'echo MARK: POST_COMPILE_LIST',
            CompileEvents::PRE_COMPILE_TASK => 'echo MARK: PRE_COMPILE_TASK',
            CompileEvents::POST_COMPILE_TASK => 'echo MARK: POST_COMPILE_TASK',
          ],
          'extra' => [
            'compile' => [
              [
                  'title' => 'Compile first',
                  'command' => 'echo MARK: RUN FIRST',
                  'passthru' => 'always',
              ],
              [
                  'title' => 'Compile second',
                  'command' => 'echo MARK: RUN SECOND',
                  'passthru' => 'always',
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
     * When running 'composer install', it run various events.
     */
    public function testComposerInstall()
    {
        $p = PH::runOk('composer install');
        $expectLines = [
            // First package: civicrm/composer-compile-plugin
            "^MARK: PRE_COMPILE_LIST",
            "^MARK: POST_COMPILE_LIST",
            // Second package: test/event-test
            "^MARK: PRE_COMPILE_LIST",
            "^MARK: POST_COMPILE_LIST",
            // First task
            "^MARK: PRE_COMPILE_TASK",
            "^MARK: RUN FIRST",
            "^MARK: POST_COMPILE_TASK",
            // Second task
            "^MARK: PRE_COMPILE_TASK",
            "^MARK: RUN SECOND",
            "^MARK: POST_COMPILE_TASK",
        ];
        $output = $p->getOutput();
        $actualLines = array_values(preg_grep(';^MARK:;', explode("\n", $output)));

        $serialize = print_r([
          'expect' => $expectLines,
          'actual' => $actualLines
        ], 1);

        $this->assertEquals(count($expectLines), count($actualLines), "Compare line count in $serialize");
        foreach ($expectLines as $offset => $expectLine) {
            $this->assertRegExp(";$expectLine;", $actualLines[$offset],
              "Check line $offset in $serialize");
        }
    }

}
