<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoTest\Simplexml;

use Magento\Simplexml\Config;

/**
 * Short description for MagentoTest\Simplexml$ConfigTest.
 *
 * Long description for MagentoTest\Simplexml$ConfigTest
 *
 * @coversDefaultClass \Magento\Simplexml\Config
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function constructorArgProvider()
    {
        // @formatter:off
        return [
            'string' => ['<?xml version="1.0"?><config><node>1</node></config>'],
            'file' => [__DIR__ . '/_files/data.xml'],
            'element' => [simplexml_load_string('<?xml version="1.0"?><config><node>1</node></config>', 'Magento\Simplexml\Element')],
            'null' => [null],
        ];
        // @formatter:on
    }

    /**
     * @covers ::__construct
     * @dataProvider constructorArgProvider
     *
     * @param $stringOrFileOrElement
     */
    public function testConstructor($stringOrFileOrElement)
    {
        $config = new Config($stringOrFileOrElement);

        if (is_string($stringOrFileOrElement) && is_readable($stringOrFileOrElement)) {
            $this->assertXmlStringEqualsXmlFile($stringOrFileOrElement, $config->getNode()
                ->asXML());
        } elseif (is_string($stringOrFileOrElement)) {
            $this->assertXmlStringEqualsXmlString($stringOrFileOrElement, $config->getNode()
                ->asXML());
        } elseif (is_object($stringOrFileOrElement)) {
            $this->assertSame($stringOrFileOrElement, $config->getNode());
        } elseif ($stringOrFileOrElement === null) {
            $this->assertFalse($config->getNode());
        } else {
            $this->fail('Unexpected test value.');
        }
    }

    /**
     * @covers ::setXml
     */
    public function testSetXml()
    {
        $el = simplexml_load_string('<?xml version="1.0"?><config><node>1</node></config>', 'Magento\Simplexml\Element');
        $config = new Config();
        $config->setXml($el);
        $this->assertSame($el, $config->getNode());
    }

    /**
     * @covers ::getNode
     */
    public function testGetNode()
    {
        $config = new Config();
        $this->assertFalse($config->getNode());

        $el = simplexml_load_string('<?xml version="1.0"?><config><node>1</node></config>', 'Magento\Simplexml\Element');
        $config = new Config($el);

        $this->assertNotNull($config->getNode());
        $this->assertSame(false, $config->getNode(''));
    }

    /**
     * @covers ::setNode
     */
    public function testSetNode()
    {
        $el = simplexml_load_string('<?xml version="1.0"?><config><node>1</node></config>', 'Magento\Simplexml\Element');

        $config = new Config($el);
        $config->setNode('node', 'foo bar');
        $this->assertSame('foo bar', (string) $config->getNode('node'));
    }

    /**
     * @covers ::loadString
     * @expectedException \PHPUnit_Framework_Error
     * @expectedExceptionMessage simplexml_load_string(): Entity: line 1: parser error : Start tag expected,
     */
    public function testLoadString()
    {
        $config = new Config();
        $xml = '<?xml version="1.0"?><config><node>1</node></config>';
        $this->assertFalse($config->loadString(''));
        $this->assertTrue($config->loadString($xml));
        $this->assertXmlStringEqualsXmlString($xml, $config->getXmlString());
        $this->assertFalse($config->loadString('wrong_path'));
    }

    /**
     * @covers ::loadDom
     */
    public function testLoadDomNode()
    {
        $xml = '<?xml version="1.0"?><config><node>1</node></config>';
        $config = new Config();
        $config->loadString($xml);
        $this->assertTrue($config->loadDom($config->getNode()));
    }

    /**
     * @covers ::getXmlString
     */
    public function testGetXmlString()
    {
        $config = new Config('<?xml version="1.0"?><a><b/><c/><d/></a>');
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?><a><b/><c/><d/></a>', $config->getXmlString());
    }

    /**
     * @covers ::applyExtends
     */
    public function testApplyExtends()
    {
        // /config/modules//resource
        $xml = '<?xml version="1.0"?><config><a><b><foo>bar</foo><baz>foo</baz></b></a><c extends="/config/a/b"></c></config>';
        $expected = '<?xml version="1.0"?><config><a><b><foo>bar</foo><baz>foo</baz></b></a><c extends="/config/a/b"><foo>bar</foo><baz>foo</baz></c></config>';
        $config = new Config($xml);
        $config->applyExtends();
        $this->assertXmlStringEqualsXmlString($expected, $config->getXmlString());

        $expected = '<?xml version="1.0"?><config><a><b><c/></b></a></config>';
        $config = new Config($expected);
        $config->applyExtends();
        $this->assertXmlStringEqualsXmlString($expected, $config->getXmlString());
    }

    public function extendDataProvider()
    {
        // @formatter:off
        return [
            ['<?xml version="1.0"?><a><b/><c/></a>', '<?xml version="1.0"?><a><b><foo>bar</foo></b><bar>baz</bar></a>', '<?xml version="1.0"?><a><b><foo>bar</foo></b><c/><bar>baz</bar></a>'],
            ['<?xml version="1.0"?><a><b/><c/></a>', '<?xml version="1.0"?><a><b><foo>bar</foo></b><bar>baz</bar><c/></a>', '<?xml version="1.0"?><a><b><foo>bar</foo></b><bar>baz</bar><c/></a>'],
        ];
        // @formatter:on
    }

    /**
     * @covers ::extend
     * @dataProvider extendDataProvider
     */
    public function testExtend($targetXml, $sourceXml, $expectedXml)
    {
        $source = new Config($sourceXml);
        $target = new Config($targetXml);

        $target->extend($source);
        $this->assertXmlStringEqualsXmlString($expectedXml, $target->getXmlString());
    }

    /**
     * @covers ::getXpath
     */
    public function testGetXpath()
    {
        $config = new Config('<a><b><c/></b><d/></a>');
        $node = $config->getXpath('/a/b');
        $this->assertTrue($node !== false);
        $this->assertNotEmpty($node);
        $this->assertInstanceOf('Magento\Simplexml\Element', $node[0]);
        $this->assertSame('b', $node[0]->getName());

        $node = $config->getXpath('/a/b/c');
        $this->assertSame('c', $node[0]->getName());

        $this->assertFalse($config->getXpath('/a/b/d'));

        $config = new Config();
        $this->assertFalse($config->getXpath('/a'));
    }

    /**
     * @covers ::loadFile
     * @covers ::processFileData
     * @depends testLoadString
     */
    public function testLoadFile()
    {
        $config = new Config();
        $this->assertFalse($config->loadFile(''));
        $this->assertTrue($config->loadFile(__DIR__ . '/_files/data.xml'));
    }
}
