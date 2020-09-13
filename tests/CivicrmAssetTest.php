<?php

namespace Civi\CompilePlugin\Tests;

use Composer\Plugin\PluginInterface;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class CivicrmAssetTest
 * @package Civi\CompilePlugin\Tests
 *
 * Like `ScssPhpMethodTest`, this uses a compilation step to make a CSS file.
 * Additionally, we enable Civi's asset plugin and assert that it publishes
 * after compilation
 */
class CivicrmAssetTest extends ScssPhpMethodTest
{
    public static function getComposerJson()
    {
        $json = parent::getComposerJson();
        $json['name'] = 'test/civicrm-asset-test';
        $json['require']['civicrm/civicrm-asset-plugin'] = '@stable';
        $json['extra']['civicrm-asset']['path'] = 'web/civi';
        return $json;
    }

    public function testComposerInstall()
    {
        $this->assertFileNotExists('web/civi/org.example.scssmethodtest/build.css');
        parent::testComposerInstall();
        $this->assertSameCssFile('vendor/test/scss-method/build.css-expected', 'web/civi/org.example.scssmethodtest/build.css');
    }
}
