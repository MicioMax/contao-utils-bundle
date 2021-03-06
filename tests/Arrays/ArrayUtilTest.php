<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\UtilsBundle\Tests\Arrays;

use Contao\System;
use Contao\TestCase\ContaoTestCase;
use HeimrichHannot\UtilsBundle\Arrays\ArrayUtil;
use HeimrichHannot\UtilsBundle\String\StringUtil;

class ArrayUtilTest extends ContaoTestCase
{
    public function setUp()
    {
        parent::setUp();

        $stringUtil = new StringUtil($this->mockContaoFramework());

        $container = $this->mockContainer();
        $container->set('huh.utils.string', $stringUtil);
        System::setContainer($container);
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $framework = $this->mockContaoFramework();
        $instance = new ArrayUtil($framework);
        $this->assertInstanceOf(ArrayUtil::class, $instance);
    }

    public function testAasort()
    {
        $framework = $this->mockContaoFramework();
        $arrayUtil = new ArrayUtil($framework);

        $array = [0 => ['filename' => 'testfile3'], 1 => ['filename' => 'testfile1'], 2 => ['filename' => 'testfile2']];

        $arrayUtil->aasort($array, 'filename');
        $this->assertSame([1 => ['filename' => 'testfile1'], 2 => ['filename' => 'testfile2'], 0 => ['filename' => 'testfile3']], $array);
    }

    public function testRemoveValue()
    {
        $framework = $this->mockContaoFramework();
        $arrayUtil = new ArrayUtil($framework);

        $array = [0 => 0, 1 => 1, 2 => 2];
        $result = $arrayUtil->removeValue(1, $array);
        $this->assertTrue($result);
        $this->assertCount(2, $array);
        $this->assertArrayHasKey(0, $array);
        $this->assertArrayHasKey(2, $array);

        $result = $arrayUtil->removeValue(1, $array);
        $this->assertFalse($result);
    }

    public function testFilterByPrefixes()
    {
        $framework = $this->mockContaoFramework();
        $arrayUtil = new ArrayUtil($framework);

        $array = ['ls_0' => 0, 1 => 1, 2 => 2];
        $result = $arrayUtil->filterByPrefixes($array);
        $this->assertSame($array, $result);

        $result = $arrayUtil->filterByPrefixes($array, [1]);
        $this->assertSame([], $result);

        $result = $arrayUtil->filterByPrefixes($array, ['ls']);
        $this->assertSame(['ls_0' => 0], $result);
    }

    public function testRemovePrefix()
    {
        $framework = $this->mockContaoFramework();
        $arrayUtil = new ArrayUtil($framework);

        $array = ['ls_prefix_1' => 1];
        $result = $arrayUtil->removePrefix('ls_', $array);
        $this->assertSame(['prefix_1' => 1], $result);
    }

    public function testArrayToObject()
    {
        $arrayUtil = new ArrayUtil($this->mockContaoFramework());
        $result = $arrayUtil->arrayToObject([]);
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertCount(0, (array) $result);

        $result = $arrayUtil->arrayToObject(['id' => 4, 'title' => 'Hallo Welt!']);
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertCount(2, (array) $result);
        $this->assertSame('Hallo Welt!', $result->title);

        $result = $arrayUtil->arrayToObject(['id' => 4, 'title' => 'Hallo Welt!', 'content' => ['a', 'b', 'c']]);
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertCount(3, (array) $result);
        $this->assertSame('Hallo Welt!', $result->title);
        $this->assertSame(['a', 'b', 'c'], $result->content);
    }

    public function testGetArrayRowByFieldValue()
    {
        $arrayUtil = new ArrayUtil($this->mockContaoFramework());
        $this->assertSame(['id' => 5, 'hallo' => 'welt5'], $arrayUtil->getArrayRowByFieldValue('id', 5, [
            ['id' => 1, 'hallo' => 'welt'],
            ['id' => 5, 'hallo' => 'welt5'],
        ]));
        $this->assertSame(['id' => 5, 'hallo' => 'welt5'], $arrayUtil->getArrayRowByFieldValue('id', 5, [
            ['id' => 1, 'hallo' => 'welt'],
            'id' => 5,
            ['id' => 5, 'hallo' => 'welt5'],
        ]));
        $this->assertSame(['id' => 5, 'hallo' => 'welt5'], $arrayUtil->getArrayRowByFieldValue('id', 5, [
            ['id' => 1, 'hallo' => 'welt'],
            ['pid' => 2, 'hallo' => 'sonnensystem2'],
            'id' => 5,
            ['id' => 5, 'hallo' => 'welt5'],
        ]));
        $this->assertSame(['id' => '5', 'hallo' => 'welt5'], $arrayUtil->getArrayRowByFieldValue('id', 5, [
            ['id' => 1, 'hallo' => 'welt'],
            'id' => 5,
            ['id' => '5', 'hallo' => 'welt5'],
        ]));
        $this->assertFalse($arrayUtil->getArrayRowByFieldValue('id', 5, [
            ['id' => 1, 'hallo' => 'welt'],
            'id' => 5,
            ['id' => '5', 'hallo' => 'welt5'],
        ], true));
        $this->assertFalse($arrayUtil->getArrayRowByFieldValue('id', 5, [
            ['id' => 1, 'hallo' => 'welt'],
            ['id' => 4, 'hallo' => 'welt4'],
        ]));
        $this->assertFalse($arrayUtil->getArrayRowByFieldValue('id', 5, ['a', 'b']));
    }

    public function testFlattenArray()
    {
        $arrayUtil = new ArrayUtil($this->mockContaoFramework());
        $this->assertSame(['hallo'], $arrayUtil->flattenArray([1 => 'hallo']));
        $this->assertSame(['hallo'], $arrayUtil->flattenArray([1 => ['hallo']]));
        $this->assertSame(['hallo'], $arrayUtil->flattenArray([1 => ['hallo']]));
        $this->assertSame(['hallo', 'welt'], $arrayUtil->flattenArray([
            1 => ['hallo', 'welt'],
        ]));
        $this->assertSame(['hallo', 'schöne', 'welt'], $arrayUtil->flattenArray([
            1 => [
                'hallo',
                ['schöne'],
                'welt', ],
        ]));
        $this->assertSame(['hallo', 'schöne', 'kleine', 'welt', '!'], $arrayUtil->flattenArray([
            1 => [
                'hallo',
                ],
            ['schöne'],
            3 => 'kleine',
            4 => [
                'welt',
                ['satzzeichen' => '!'],
            ],
        ]));
    }
}
