<?php

/**
 * Magento.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace SirrusTest\Simplexml;

/**
 * Short description for SirrusTest\Simplexml$ElementTest.
 *
 * Long description for SirrusTest\Simplexml$ElementTest
 *
 * @coversDefaultClass \Sirrus\Simplexml\Element
 *
 * @copyright Copyright (c) 2014 Sirrus Systems GmbH (http://www.sirrus-systems.de/)
 * @license   http://www.sirrus-systems.de/spf/license.html
 *
 * @version   $Id$
 *
 * @since     Class available since revision $Revision$
 */
class ElementTest extends \PHPUnit_Framework_TestCase
{
    public function unsetDataProvider()
    {
        // @formatter:off
        return [
            ['<config><a><b><c/></b></a><d/></config>', '<config><d/></config>', 'a'],
            ['<config></config>', '<config></config>'],
            ['<config><a><b><c/></b></a><d/></config>', '<config><a><b><c/></b></a><d/></config>'],
        ];
        // @formatter:off
    }

    /**
     * @dataProvider unsetDataProvider
     */
    public function testUnsetSelf($xml, $expectedXml, $child = null, $exceptionName = null, $exceptionMessage = '')
    {
        /*
         * @var $xml \Magento\Simplexml\Element
         */
        $el = simplexml_load_string($xml, 'Magento\Simplexml\Element');

        $this->setExpectedException($exceptionName, $exceptionMessage);

        if ($child !== null) {
            $el->{$child}->unsetSelf();
        } else {
            $el->unsetSelf();
        }

        $this->assertXmlStringEqualsXmlString($expectedXml, $el->asXML());
    }

    public function testHasChildrenWithAttributes()
    {
        $data = <<<XML
<root>
       <node testAttribute="testValue" />
</root>
XML;
        $xml = simplexml_load_string($data, 'Magento\Simplexml\Element');
        $node = $xml->xpath('/root/node')[0];
        $this->assertNotEmpty($node);
        $this->assertFalse($node->hasChildren());
    }

    public function testGetAttributes()
    {
        $data = <<<XML
<root>
       <node testAttribute="testValue" />
</root>
XML;
        $xml = simplexml_load_string($data, 'Magento\Simplexml\Element');
        $node = $xml->xpath('/root/node')[0];
        $this->assertNotEmpty($node);
        $this->assertEquals('testValue', $node->getAttribute('testAttribute'));
    }

    public function testAsArray()
    {
        $data = <<<XML
<root><node testAttribute="testValue" /></root>
XML;
        $xml = simplexml_load_string($data, 'Magento\Simplexml\Element');
        $this->assertSame([
            'node' => [
                '@' => [
                    'testAttribute' => 'testValue',
                ],
                '',
            ],
        ], $xml->asArray());
    }

    public function testAsCanonicalArray()
    {
        $data = <<<XML
<root><node testAttribute="testValue"><node_1 /></node></root>
XML;
        $xml = simplexml_load_string($data, 'Magento\Simplexml\Element');
        $this->assertSame([
            'node' => [
                'node_1' => '',
            ],
        ], $xml->asCanonicalArray());
    }

    public function testAsNiceXmlMixedData()
    {
        $dataFile = file_get_contents(__DIR__ . '/_files/mixed_data.xml');
        /**
         * @var $xml \Magento\Simplexml\Element
         */
        $xml = simplexml_load_string($dataFile, 'Magento\Simplexml\Element');

        $expected = <<<XML
<root>
    <node_1 id="1">Value 1
        <node_1_1>Value 1.1
            <node_1_1_1>Value 1.1.1</node_1_1_1>
        </node_1_1>
    </node_1>
    <node_2>
        <node_2_1>Value 2.1</node_2_1>
    </node_2>
</root>

XML;
        $this->assertEquals($expected, $xml->asNiceXml());
    }

    public function testAppendChild()
    {
        /**
         * @var $xml \Magento\Simplexml\Element
         */
        $baseXml = simplexml_load_string('<root/>', 'Magento\Simplexml\Element');
        /**
         * @var $xml \Magento\Simplexml\Element
         */
        $appendXml = simplexml_load_string('<node_a attr="abc"><node_b>text</node_b></node_a>', 'Magento\Simplexml\Element');
        $baseXml->appendChild($appendXml);

        $expectedXml = '<root><node_a attr="abc"><node_b>text</node_b></node_a></root>';
        $this->assertXmlStringEqualsXmlString($expectedXml, $baseXml->asNiceXml());
    }

    public function testSetNode()
    {
        $path = '/node1/node2';
        $value = 'value';
        /**
         * @var $xml \Magento\Simplexml\Element
         */
        $xml = simplexml_load_string('<root/>', 'Magento\Simplexml\Element');
        $this->assertEmpty($xml->xpath('/root/node1/node2'));
        $xml->setNode($path, $value);
        $this->assertNotEmpty($xml->xpath('/root/node1/node2'));
        $this->assertEquals($value, (string) $xml->xpath('/root/node1/node2')[0]);
    }

    public function testExtend()
    {
        /**
         * @var $xml \Magento\Simplexml\Element
         */
        $xml = simplexml_load_string('<root/>', 'Magento\Simplexml\Element');
        $xml2 = simplexml_load_string('<root><foo/><bar/><baz/></root>', 'Magento\Simplexml\Element');
        $xml->extend($xml2);
        $this->assertTrue(true);
    }
}
