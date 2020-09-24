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
                      '@php -r \'echo "MARK: PHPCODE\n";\'',
                      '@putenv FOO=123',
                      '@sh echo MARK: FOO IS $FOO',
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
            "^MARK: PHPCODE",
            "^MARK: FOO IS 123",
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
