<?php

namespace PHPSemVerCheckerGit\Test;

use PHPSemVerCheckerGit\Console\Command\SuggestCommand;
use PHPUnit\Framework\TestCase;
use PHPSemVerChecker\SemanticVersioning\Level;
use vierbergenlars\SemVer\version;

class SuggestCommandTest extends TestCase
{
    /**
     * @todo properly mock
     * @param int $major
     * @param int $minor
     * @param int $patch
     * @return version
     */
    private function getMockedVersion($major, $minor, $patch)
    {
        return new version("$major.$minor.$patch", true);
    }

    /**
     * Provides a few test cases
     * @return array
     */
    public function provideGetNextTag() {
        return array(
            //v0.1.0
            array(Level::NONE, $this->getMockedVersion(0,1,0), '0.1.0'),
            array(Level::PATCH, $this->getMockedVersion(0,1,0), '0.1.1'),
            array(Level::MINOR, $this->getMockedVersion(0,1,0), '0.2.0'),
            array(Level::MAJOR, $this->getMockedVersion(0,1,0), '0.2.0'),
            //v1.0.0
            array(Level::NONE, $this->getMockedVersion(1,0,0), '1.0.0'),
            array(Level::PATCH, $this->getMockedVersion(1,0,0), '1.0.1'),
            array(Level::MINOR, $this->getMockedVersion(1,0,0), '1.1.0'),
            array(Level::MAJOR, $this->getMockedVersion(1,0,0), '2.0.0'),
            //v2.3.4
            array(Level::NONE, $this->getMockedVersion(2,3,4), '2.3.4'),
            array(Level::PATCH, $this->getMockedVersion(2,3,4), '2.3.5'),
            array(Level::MINOR, $this->getMockedVersion(2,3,4), '2.4.0'),
            array(Level::MAJOR, $this->getMockedVersion(2,3,4), '3.0.0'),
        );
    }

    /**
     * @param $level
     * @param $version
     * @param $expected
     * @throws \ReflectionException
     * @test
     * @dataProvider provideGetNextTag
     */
    public function testGetNextTag($level, version $version, $expected) {
        $report = $this->getMockBuilder('PHPSemVerChecker\Report\Report')->disableOriginalConstructor()->getMock();
        $report->expects($this->once())->method('getSuggestedLevel')->willReturn($level);
        $instance = new SuggestCommand();
        $rc = new \ReflectionClass('PHPSemVerCheckerGit\Console\Command\SuggestCommand');
        $method = $rc->getMethod('getNextTag');
        $method->setAccessible(true);
        $result = $method->invoke($instance, $report, $version);
        $this->assertInstanceOf('vierbergenlars\SemVer\version', $result);
        $this->assertEquals($expected, $result->getVersion());
    }
}