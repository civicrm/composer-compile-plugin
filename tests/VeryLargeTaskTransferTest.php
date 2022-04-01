<?php

namespace Civi\CompilePlugin\Tests;

use Civi\CompilePlugin\Event\CompileEvents;
use Civi\CompilePlugin\TaskTransfer;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class EventTest
 * @package Civi\CompilePlugin\Tests
 *
 * This test involves sending a large amoung of task metadata from the
 * main 'compile' process to the subordinate processes.
 *
 * @see \Civi\CompilePlugin\TaskTransfer
 */
class VeryLargeTaskTransferTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        return parent::getComposerJson() + [
          'name' => 'test/event-test',
          'require' => [
            'civicrm/composer-compile-plugin' => '@dev',
          ],
          'minimum-stability' => 'dev',
          'extra' => [
            'compile' => [
              [
                'title' => 'Compile first',
                'run' => [
                  '@php-eval printf("PAYLOAD: SIZE: %d\n", strlen($GLOBALS["COMPOSER_COMPILE_TASK"]["payload"]));',
                  '@php-eval printf("PAYLOAD: MODE: %s\n", getenv("COMPOSER_COMPILE_TASK")[0] === "@" ? "file" : "b64-gz-js");',
                ],
                'payload' => self::computeLargePayload()
              ],
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
     * When running 'composer install', it run various events.
     */
    public function testComposerInstall()
    {
        $p = PH::runOk('COMPOSER_COMPILE_PASSTHRU=always COMPOSER_COMPILE=1 composer install');
        $expectLines = [
          "^PAYLOAD: SIZE: " . strlen(self::computeLargePayload()),
          "^PAYLOAD: MODE: file",
        ];
        $this->assertOutputLines($expectLines, ';^PAYLOAD:;', $p->getOutput());
    }

    /**
     * Make a string that is so longer/diverse that it cannot be transferred
     * via environment-variable.
     *
     * @return string
     */
    protected static function computeLargePayload()
    {
        static $longString = null;
        if ($longString === null) {
            $encode = function ($x) {
                return base64_encode(gzencode(json_encode($x)));
            };
            $compressionBuster = function ($base, $delta) {
                $r = '';
                for ($i = 0; $i < strlen($base); $i++) {
                    $r .= chr($delta + ord($base[$i]));
                }
                return $r;
            };

            $base = file_get_contents(__FILE__);
            $delta = 0;
            $longString = '';
            while (strlen($encode($longString)) < TaskTransfer::MAX_ENV_SIZE) {
                $longString .= $compressionBuster($base, $delta);
                $delta++;
            }
        }
        return $longString;
    }
}
