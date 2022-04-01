<?php

namespace Civi\CompilePlugin\Tests;

use Civi\CompilePlugin\Event\CompileEvents;
use Civi\CompilePlugin\Util\EnvHelper;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class ErrorOutputTest
 * @package Civi\CompilePlugin\Tests
 *
 * This is general integration test of the plugin. It creates an example project which uses the
 * current/under-development plugin.  It asserts that various events fire.
 *
 * We check this by having each event echo some text of the form `MARK: something-happend`.
 * We then do an assertion on the list of `^MARK:` statements.
 */
class ErrorOutputTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        return parent::getComposerJson() + [
          'name' => 'test/error-output-test',
          'require' => [
              'civicrm/composer-compile-plugin' => '@dev',
          ],
          'minimum-stability' => 'dev',
          'extra' => [
            'compile' => [
              [
                  'title' => 'Compile first',
                  'shell' => 'echo MARK: RUN FIRST; [ -n $ERROR_1 ]; exit $ERROR_1',
              ],
              [
                  'title' => 'Compile second',
                  'shell' => 'echo MARK: RUN SECOND; [ -n $ERROR_2 ]; exit $ERROR_2',
              ],
              [
                  'title' => 'Compile third',
                  'shell' => 'echo MARK: RUN THIRD; [ -n $ERROR_3 ]; exit $ERROR_3',
              ]
            ],
          ],
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::initTestProject(static::getComposerJson());
    }

    /**
     * Run a successful command, with passthru=always
     */
    public function testPassthruAlways()
    {
        $p = PH::runOk('COMPOSER_COMPILE_PASSTHRU=always COMPOSER_COMPILE=1 composer install');
        $expectLines = [
            "^MARK: RUN FIRST",
            "^MARK: RUN SECOND",
            "^MARK: RUN THIRD",
        ];
        $this->assertOutputLines($expectLines, ';^MARK:;', $p->getOutput());
    }

    /**
     * Run a successful command, with passthru=always
     */
    public function testPassthruNever()
    {
        $p = PH::runOk('COMPOSER_COMPILE_PASSTHRU=never COMPOSER_COMPILE=1 composer install');
        $expectLines = [];
        $this->assertOutputLines($expectLines, ';^MARK:;', $p->getOutput());
    }

    /**
     * Run a successful command, with passthru=always
     */
    public function testPassthruAlwaysWithError()
    {
        $p = PH::run('ERROR_2=1 COMPOSER_COMPILE_PASSTHRU=always COMPOSER_COMPILE=1 composer install');
        $expectLines = [
            "^MARK: RUN FIRST",
            "^MARK: RUN SECOND",
            // There's an error, so third task doesn't run.
        ];
        $this->assertOutputLines($expectLines, ';^MARK:;', $p->getOutput());
        $this->assertTrue($p->getExitCode() !== 0);
    }

    /**
     * Run a successful command, with passthru=always
     */
    public function testPassthruError()
    {
        $p = PH::run('ERROR_2=1 COMPOSER_COMPILE_PASSTHRU=error COMPOSER_COMPILE=1 composer install');
        $expectLines = [
            // First task succeeds, so it doesn't output details.
            "^MARK: RUN SECOND",
            // There's an error, so third task doesn't run.
        ];
        $this->assertOutputLines($expectLines, ';^MARK:;', $p->getOutput());
        $this->assertTrue($p->getExitCode() !== 0);
    }
}
