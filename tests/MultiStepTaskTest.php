<?php

namespace Civi\CompilePlugin\Tests;

use Civi\CompilePlugin\Event\CompileEvents;
use Civi\CompilePlugin\Util\EnvHelper;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class MultistepTaskTest
 * @package Civi\CompilePlugin\Tests
 *
 *
 */
class MultiStepTaskTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        return parent::getComposerJson() + [
          'name' => 'test/multi-step-task-test',
          'require' => [
              'civicrm/composer-compile-plugin' => '@dev',
          ],
          'minimum-stability' => 'dev',
          'autoload' => [
            'files' => ['example-method.php'],
          ],
          'extra' => [
            'compile' => [
              [
                  'title' => 'Do multiple things',
                  'run' => [
                      '@sh echo MARK: SH FIRST; [ -n $ERROR_SH_1 ] && exit $ERROR_SH_1',
                      '@sh echo MARK: SH SECOND; [ -n $ERROR_SH_2 ] && exit $ERROR_SH_2',
                      '@sh echo MARK: SH THIRD; [ -n $ERROR_SH_3 ] && exit $ERROR_SH_3',
                      '@php-method MultistepEx::doFirst',
                      '@php-method MultistepEx::doSecond',
                      '@php-method MultistepEx::doThird',
                      '@php -r \'echo "MARK: PHPCMD\n";\'',
                      '@php-eval echo "MARK: PHPEVAL\\n";',
                      '@php-script example-script.php and stuff',
                      '@export MISSING={{pkg:test/m-i-s-s-i-n-g}} COMPLG={{pkg:civicrm/composer-compile-plugin}}',
                      '@export SELF={{pkg:test/multi-step-task-test}}',
                      '@sh echo "MARK: Missing package is \'$MISSING\'"',
                      '@sh echo "MARK: Test package is \'$SELF\'"',
                      '@sh echo "MARK: Compile plugin is \'$COMPLG\'"',
                      '@putenv FOO=123',
                      '@sh echo "MARK: FOO IS \'$FOO\'"',
                  ],
              ],
              [
                  'title' => 'Do another thing',
                  'run' => [
                    '@sh echo "MARK: FOO IS LATER \'$FOO\'"',
                    '@sh echo "MARK: COMPLG IS LATER \'$COMPLG\'"',
                  ],
              ],
            ],
          ],
        ];
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::initTestProject(static::getComposerJson());
        file_put_contents(
            self::getTestDir() . '/example-script.php',
            implode("\n", [
                '<' . '?php',
                'echo "MARK: PHPSCRIPT ";',
                'global $argv; $extra = $argv; array_shift($extra);',
                'echo implode(" ", $extra);',
                'echo class_exists("\Civi\CompilePlugin\Task") ? " WITH-AUTOLOAD" : " WITHOUT-AUTLOAD";',
                'echo !empty($GLOBALS["COMPOSER_COMPILE_TASK"]["run"]) ? " WITH-TASK" : " WITHOUT-TASK";',
                'echo "\n";'
            ])
        );
        file_put_contents(
            self::getTestDir() . '/example-method.php',
            implode("\n", [
                '<' . '?php',
                'class MultistepEx {',
                'function doFirst($task){ echo "MARK: PHP FIRST\n"; if (getenv("ERROR_PHP_1")){ exit(1); } }',
                'function doSecond($task){ echo "MARK: PHP SECOND\n"; if (getenv("ERROR_PHP_2")){ exit(1); } }',
                'function doThird($task){ echo "MARK: PHP THIRD\n"; if (getenv("ERROR_PHP_3")){ exit(1); } }',
                '}',
            ])
        );
    }

    /**
     * Run a successful command, with passthru=always
     */
    public function testAllSubtasksOk()
    {
        $p = PH::runOk('COMPOSER_COMPILE_PASSTHRU=always COMPOSER_COMPILE=1 composer install');
        $expectLines = [
            "^MARK: SH FIRST",
            "^MARK: SH SECOND",
            "^MARK: SH THIRD",
            "^MARK: PHP FIRST",
            "^MARK: PHP SECOND",
            "^MARK: PHP THIRD",
            "^MARK: PHPCMD",
            "^MARK: PHPEVAL",
            "^MARK: PHPSCRIPT and stuff WITH-AUTOLOAD WITH-TASK",
            '^MARK: Missing package is \'\'$',
            '^MARK: Test package is \'' . realpath(self::getTestDir()) . '\'',
            '^MARK: Compile plugin is \'' . realpath(self::getTestDir()) . '/vendor/civicrm/composer-compile-plugin\'',
            "^MARK: FOO IS '123'",
            // FOO should not propagate from the environment of task #1 to task #2.
            "^MARK: FOO IS LATER ''",
            "^MARK: COMPLG IS LATER ''",
        ];
        $this->assertOutputLines($expectLines, ';^MARK:;', $p->getOutput());
    }

    /**
     * Run a list of shell/php items, with a failure in the middle
     */
    public function testErrorShSecond()
    {
        $p = PH::run('ERROR_SH_2=2 COMPOSER_COMPILE_PASSTHRU=always COMPOSER_COMPILE=1 composer install');
        $expectLines = [
            "^MARK: SH FIRST",
            "^MARK: SH SECOND",
        ];
        $this->assertOutputLines($expectLines, ';^MARK:;', $p->getOutput());
        $this->assertTrue($p->getExitCode() !== 0);
    }

    /**
     * Run a list of shell/php items, with a failure in the middle
     */
    public function testErrorPhpSecond()
    {
        $p = PH::run('ERROR_PHP_2=2 COMPOSER_COMPILE_PASSTHRU=always COMPOSER_COMPILE=1 composer install');
        $expectLines = [
            "^MARK: SH FIRST",
            "^MARK: SH SECOND",
            "^MARK: SH THIRD",
            "^MARK: PHP FIRST",
            "^MARK: PHP SECOND",
        ];
        $this->assertOutputLines($expectLines, ';^MARK:;', $p->getOutput());
        $this->assertTrue($p->getExitCode() !== 0);
    }
}
