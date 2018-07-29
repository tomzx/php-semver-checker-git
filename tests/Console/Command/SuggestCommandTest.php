<?php

namespace PHPSemVerCheckerGit\Tests;

use PHPSemVerChecker\SemanticVersioning\Level;
use PHPSemVerCheckerGit\Console\Command\SuggestCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use vierbergenlars\SemVer\version;

class SuggestCommandTest extends TestCase
{
    /**
     * @todo properly mock
     * @param int $major
     * @param int $minor
     * @param int $patch
     * @return \vierbergenlars\SemVer\version
     */
    private function getMockedVersion($major, $minor, $patch)
    {
        return new version("$major.$minor.$patch", true);
    }

    /**
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    private function getNextTagMethod()
    {
        $class = new ReflectionClass('PHPSemVerCheckerGit\Console\Command\SuggestCommand');
        $method = $class->getMethod('getNextTag');
        $method->setAccessible(true);
        return $method;
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
            //v1.0.0RC1
            array(Level::NONE, $this->getMockedVersion(1,0,'0-1'), '1.0.0-1'),
            array(Level::PATCH, $this->getMockedVersion(1,0,'0-1'), '1.0.0-2'),
            array(Level::MINOR, $this->getMockedVersion(1,0,'0-1'), '1.0.0-2'),
            array(Level::MAJOR, $this->getMockedVersion(1,0,'0-1'), '1.0.0-2'),
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
     * @param \vierbergenlars\SemVer\version $version
     * @param $expected
     * @throws \ReflectionException
     * @test
     * @dataProvider provideGetNextTag
     */
    public function testGetNextTag($level, version $version, $expected)
    {
        $report = $this->getMockBuilder('PHPSemVerChecker\Report\Report')->disableOriginalConstructor()->getMock();
        $report->expects($this->once())->method('getSuggestedLevel')->willReturn($level);

        $result = $this->getNextTagMethod()->invoke(new SuggestCommand(), $report, $version);
        $this->assertInstanceOf('vierbergenlars\SemVer\version', $result);
        $this->assertEquals($expected, $result->getVersion());
    }
}
