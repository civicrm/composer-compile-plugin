<?php
namespace Civi\CompilePlugin\Tests;

use Civi\CompilePlugin\Util\TsHelper;

class TsHelperTest extends IntegrationTestCase
{

    const REFTIME = 1601521700;

    public static function initTestProject($composerJson)
    {
        parent::initTestProject($composerJson);
        $testDir = self::getTestDir();

        mkdir("$testDir/input/sub1/sub2", 0777, true);
        mkdir("$testDir/output/sub1/sub2", 0777, true);
        $ps = [
            "$testDir/input/sub1/sub2/one.src",
            "$testDir/input/sub1/sub2/two.src",
            "$testDir/output/sub1/sub2/one.bld",
            "$testDir/output/sub1/sub2/two.bld",
            "$testDir/output/sub1/sub2",
            "$testDir/output/sub1",
            "$testDir/output",
            "$testDir/input/sub1/sub2",
            "$testDir/input/sub1",
            "$testDir/input",
        ];
        foreach ($ps as $p) {
            touch($p, self::REFTIME);
        }
    }

    public function testAddOutput()
    {
        self::initTestProject([]);
        $testDir = self::getTestDir();

        $fs = new TsHelper(null, null);
        $this->assertTrue($fs->isFresh("$testDir/output", "$testDir/input"));

        touch("$testDir/output/asdf");
        $this->assertTrue($fs->isFresh("$testDir/output", "$testDir/input"));
    }

    public function testNonExistentOutput()
    {
        self::initTestProject([]);
        $testDir = self::getTestDir();

        $fs = new TsHelper(null, null);
        $this->assertFalse($fs->isFresh(["$testDir/output", "$testDir/otherout"], "$testDir/input"));
    }

    public function testNonExistentInput()
    {
        self::initTestProject([]);
        $testDir = self::getTestDir();

        $fs = new TsHelper(null, null);
        $this->assertFalse($fs->isFresh(["$testDir/output"], ["$testDir/input", "$testDir/other-in"]));
    }

    public function testAddInput()
    {
        self::initTestProject([]);
        $testDir = self::getTestDir();

        $fs = new TsHelper(null, null);
        $this->assertTrue($fs->isFresh("$testDir/output", "$testDir/input"));

        touch("$testDir/input/sub1/fdsa");
        $this->assertFalse($fs->isFresh("$testDir/output", "$testDir/input"));
    }

    public function testUpdateInput()
    {
        self::initTestProject([]);
        $testDir = self::getTestDir();

        $fs = new TsHelper(null, null);
        $this->assertTrue($fs->isFresh("$testDir/output", "$testDir/input"));

        touch("$testDir/input/sub1/sub2/one.src");
        $this->assertFalse($fs->isFresh("$testDir/output", "$testDir/input"));
    }

    public function testRenameDir()
    {
        self::initTestProject([]);
        $testDir = self::getTestDir();

        $fs = new TsHelper(null, null);
        $this->assertTrue($fs->isFresh("$testDir/output", "$testDir/input"));

        rename("$testDir/input/sub1", "$testDir/input/sububub");
        $this->assertFalse($fs->isFresh("$testDir/output", "$testDir/input"));
    }
}
