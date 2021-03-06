<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Simplexml;

/**
 * Base class for simplexml based configurations.
 *
 * @since Class available since revision $Revision$
 */
class Config
{
    /**
     * Configuration xml.
     *
     * @var \Magento\Framework\Simplexml\Element
     */
    protected $xml = null;

    /**
     * Class name of simplexml elements for this configuration.
     *
     * @var string
     */
    protected $elementClass = 'Magento\Framework\Simplexml\Element';

    /**
     * Xpath describing nodes in configuration that need to be extended.
     *
     * @example <allResources extends="/config/modules//resource"/>
     */
    protected $xpathExtends = "//*[@extends]";

    /**
     * Constructor.
     *
     * Initializes XML for this configuration
     *
     * @see  self::setXml
     *
     * @param string|\Magento\Framework\Simplexml\Element $sourceData
     * @param string                                      $sourceType
     */
    public function __construct($sourceData = null)
    {
        if (is_null($sourceData)) {
            return;
        }

        if ($sourceData instanceof Element) {
            $this->setXml($sourceData);
        } elseif (is_string($sourceData) && !empty($sourceData)) {
            if (strlen($sourceData) < 1000 && is_readable($sourceData)) {
                $this->loadFile($sourceData);
            } else {
                $this->loadString($sourceData);
            }
        }
    }

    /**
     * Sets xml for this configuration.
     *
     * @param \Magento\Framework\Simplexml\Element $node
     *
     * @return Config
     */
    public function setXml(Element $node)
    {
        $this->xml = $node;

        return $this;
    }

    /**
     * Imports XML file.
     *
     * @param string $filePath
     *
     * @return boolean
     */
    public function loadFile($filePath)
    {
        if (!is_readable($filePath)) {
            return false;
        }

        $fileData = file_get_contents($filePath);
        $fileData = $this->processFileData($fileData);

        return $this->loadString($fileData);
    }

    /**
     * Stub method for processing file data right after loading the file text.
     *
     * @param string $text
     *
     * @return string
     */
    public function processFileData($text)
    {
        return $text;
    }

    /**
     * Imports XML string.
     *
     * @param string $string
     *
     * @return boolean
     */
    public function loadString($string)
    {
        if (is_string($string)) {
            $xml = simplexml_load_string($string, $this->elementClass);
            if ($xml instanceof Element) {
                $this->xml = $xml;

                return true;
            }
        }

        return false;
    }

    /**
     * Return Xml of node as string.
     *
     * @return string
     */
    public function getXmlString()
    {
        return $this->getNode()->asNiceXml('', false);
    }

    /**
     * Returns node found by the $path.
     *
     * @see \Magento\Framework\Simplexml\Element::descend
     *
     * @param string $path
     *
     * @return boolean|\Magento\Framework\Simplexml\Element
     */
    public function getNode($path = null)
    {
        if (!$this->xml instanceof Element) {
            return false;
        } elseif ($path === null) {
            return $this->xml;
        } else {
            return $this->xml->descend($path);
        }
    }

    /**
     * Imports DOM node.
     *
     * @param \DOMNode $dom
     *
     * @return boolean|\Magento\Framework\Simplexml\Element
     */
    public function loadDom($dom)
    {
        $xml = simplexml_import_dom($dom, $this->elementClass);

        if ($xml) {
            $this->xml = $xml;

            return true;
        }

        return false;
    }

    /**
     * Create node by $path and set its value.
     *
     * @param string  $path
     *                           separated by slashes
     * @param string  $value
     * @param boolean $overwrite
     *
     * @return \Magento\Framework\Simplexml\Config
     */
    public function setNode($path, $value, $overwrite = true)
    {
        $xml = $this->xml->setNode($path, $value, $overwrite);

        return $this;
    }

    /**
     * Process configuration xml.
     *
     * @return \Magento\Framework\Simplexml\Config
     */
    public function applyExtends()
    {
        $targets = $this->getXpath($this->xpathExtends);
        if (!$targets) {
            return $this;
        }

        foreach ($targets as $target) {
            $sources = $this->getXpath((string)$target['extends']);

            if ($sources) {
                foreach ($sources as $source) {
                    $target->extend($source);
                }
                // } else {
                // echo "Not found extend: ".(string)$target['extends'];
                // }
            }
            // unset($target['extends']);
        }

        return $this;
    }

    /**
     * Returns nodes found by xpath expression.
     *
     * @param string $xpath
     *
     * @return boolean|array
     */
    public function getXpath($xpath)
    {
        if (!$this->xml instanceof Element || empty($this->xml)) {
            return false;
        }

        if (!$result = $this->xml->xpath($xpath)) {
            return false;
        }

        return $result;
    }

    /**
     * Load and merge files into object.
     *
     * @param array $files list of xml files
     * @param bool  $overwrite
     *
     * @return $this
     */
    public function mergeFiles(array $files = [], $overwrite = true)
    {
        $prototype = new self();
        foreach ($files as $xmlFile) {
            if ($prototype->loadFile($xmlFile)) {
                $this->extend($prototype, $overwrite);
            }
        }
        return $this;
    }

    /**
     * Extends current node with xml from $config.
     *
     * If $overwrite is false will merge only missing nodes
     * Otherwise will overwrite existing nodes
     *
     * @param \Magento\Framework\Simplexml\Config $config
     * @param boolean                             $overwrite
     *
     * @return \Magento\Framework\Simplexml\Config
     */
    public function extend(Config $config, $overwrite = true)
    {
        $this->getNode()->extend($config->getNode(), $overwrite);

        return $this;
    }

    /**
     * Cleanup circular references.
     *
     * Destructor should be called explicitly in order to work around the PHP bug
     * https://bugs.php.net/bug.php?id=62468
     */
    public function __destruct()
    {
        $this->xml = null;
    }
}
