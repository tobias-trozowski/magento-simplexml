<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Simplexml;

/**
 * Extends SimpleXML to add valuable functionality to SimpleXMLElement class.
 *
 * @since Class available since revision $Revision$
 */
class Element extends \SimpleXMLElement
{
    /**
     * Would keep reference to parent node.
     *
     * If SimpleXMLElement would support complicated attributes
     *
     * @todo make use of spl_object_hash to keep global array of simplexml elements
     *       to emulate complicated attributes
     *
     * @var \Magento\Framework\Simplexml\Element
     */
    protected $parentElement = null;

    /**
     * For future use.
     *
     * @param \Magento\Framework\Simplexml\Element $element
     *
     * @return $this
     */
    public function setParent(Element $element)
    {
        // $this->parentElement = $element;
        return $this;
    }

    /**
     * Returns attribute value by attribute name.
     *
     * @param string $name
     *
     * @return string
     */
    public function getAttribute($name)
    {
        $attributes = $this->attributes();

        return isset($attributes[$name]) ? (string)$attributes[$name] : null;
    }

    /**
     * Find a descendant of a node by path.
     *
     * @todo Do we need to make it xpath look-a-like?
     * @todo Check if we still need all this and revert to plain XPath if this makes any sense
     * @todo param string $path Subset of xpath. Example: "child/grand[@attrName='attrValue']/subGrand"
     *
     * @param string $path
     *                     Example: "child/grand@attrName=attrValue/subGrand" (to make it faster without regex)
     *
     * @return \Magento\Framework\Simplexml\Element
     */
    public function descend($path)
    {
        // $node = $this->xpath($path);
        // return $node[0];
        if (is_array($path)) {
            $pathArr = $path;
        } else {
            // Simple exploding by / does not suffice,
            // as an attribute value may contain a / inside
            // Note that there are three matches for different kinds of attribute values specification
            if (strpos($path, "@") === false) {
                $pathArr = explode('/', $path);
            } else {
                $regex = "#([^@/\\\"]+(?:@[^=/]+=(?:\\\"[^\\\"]*\\\"|[^/]*))?)/?#";
                $pathArr = $pathMatches = [];
                if (preg_match_all($regex, $path, $pathMatches)) {
                    $pathArr = $pathMatches[1];
                }
            }
        }
        $desc = $this;
        foreach ($pathArr as $nodeName) {
            if (strpos($nodeName, '@') !== false) {
                $a = explode('@', $nodeName);
                $b = explode('=', $a[1]);
                $nodeName = $a[0];
                $attributeName = $b[0];
                $attributeValue = $b[1];
                //
                // Does a very simplistic trimming of attribute value.
                //
                $attributeValue = trim($attributeValue, '"');
                $found = false;
                foreach ($desc->$nodeName as $subdesc) {
                    if ((string)$subdesc[$attributeName] === $attributeValue) {
                        $found = true;
                        $desc = $subdesc;
                        break;
                    }
                }
                if (!$found) {
                    $desc = false;
                }
            } else {
                $desc = $desc->$nodeName;
            }
            if (!$desc) {
                return false;
            }
        }

        return $desc;
    }

    /**
     * Returns the node and children as an array.
     *
     * @return array string
     */
    public function asArray()
    {
        return $this->toArray();
    }

    /**
     * Returns the node and children as an array.
     *
     * @param bool $isCanonical
     *                          - whether to ignore attributes
     *
     * @return array string
     */
    protected function toArray($isCanonical = false)
    {
        $result = [];
        if (!$isCanonical) {
            // add attributes
            foreach ($this->attributes() as $attributeName => $attribute) {
                if ($attribute) {
                    $result['@'][$attributeName] = (string)$attribute;
                }
            }
        }
        // add children values
        if ($this->hasChildren()) {
            foreach ($this->children() as $childName => $child) {
                /* @var Element $child */
                $result[$childName] = $child->toArray($isCanonical);
            }
        } else {
            if (empty($result)) {
                // return as string, if nothing was found
                $result = (string)$this;
            } else {
                // value has zero key element
                $result[0] = (string)$this;
            }
        }

        return $result;
    }

    /**
     * Enter description here...
     *
     * @return boolean
     */
    public function hasChildren()
    {
        if (!$this->children()) {
            return false;
        }
        // simplexml bug: @attributes is in children() but invisible in foreach
        foreach ($this->children() as $k => $child) {
            return true;
        }

        return false;
    }

    /**
     * asArray() analog, but without attributes.
     *
     * @return array string
     */
    public function asCanonicalArray()
    {
        return $this->toArray(true);
    }

    /**
     * Makes nicely formatted XML from the node.
     *
     * @param string      $filename
     * @param int|boolean $level
     *                              if false
     *
     * @return string
     */
    public function asNiceXml($filename = '', $level = 0)
    {
        if (is_numeric($level)) {
            $pad = str_pad('', $level * 4, ' ', STR_PAD_LEFT);
            $nl = "\n";
        } else {
            $pad = '';
            $nl = '';
        }

        $out = $pad . '<' . $this->getName();

        $attributes = $this->attributes();
        if ($attributes) {
            foreach ($attributes as $key => $value) {
                $out .= ' ' . $key . '="' . str_replace('"', '\"', (string)$value) . '"';
            }
        }

        $attributes = $this->attributes('xsi', true);
        if ($attributes) {
            foreach ($attributes as $key => $value) {
                $out .= ' xsi:' . $key . '="' . str_replace('"', '\"', (string)$value) . '"';
            }
        }

        if ($this->hasChildren()) {
            $out .= '>';
            $value = trim((string)$this);
            if (strlen($value)) {
                $out .= $this->xmlentities($value);
            }
            $out .= $nl;
            foreach ($this->children() as $child) {
                /* @var Element $child */
                $out .= $child->asNiceXml('', is_numeric($level) ? $level + 1 : true);
            }
            $out .= $pad . '</' . $this->getName() . '>' . $nl;
        } else {
            $value = (string)$this;
            if (strlen($value)) {
                $out .= '>' . $this->xmlentities($value) . '</' . $this->getName() . '>' . $nl;
            } else {
                $out .= '/>' . $nl;
            }
        }

        if ((0 === $level || false === $level) && !empty($filename)) {
            file_put_contents($filename, $out);
        }

        return $out;
    }

    /**
     * Converts meaningful xml characters to xml entities.
     *
     * @param
     *            string
     *
     * @return string
     */
    public function xmlentities($value = null)
    {
        if (is_null($value)) {
            $value = $this;
        }
        $value = (string)$value;
        /* @formatter:off */
        $value = str_replace(
            ['&', '"', "'", '<', '>'],
            ['&amp;', '&quot;', '&apos;', '&lt;', '&gt;'],
            $value
        );
        /* @formatter:on */
        return $value;
    }

    /**
     * Generates inner XML string.
     *
     * @param int $level
     *
     * @return string
     */
    public function innerXml($level = 0)
    {
        $out = '';
        foreach ($this->children() as $child) {
            /* @var Element $child */
            $out .= $child->asNiceXml($level);
        }

        return $out;
    }

    /**
     * Appends $source to current node.
     *
     * @param \Magento\Framework\Simplexml\Element $source
     *
     * @return \Magento\Framework\Simplexml\Element
     */
    public function appendChild($source)
    {
        /* @var Element $child */
        if ($source->children()) {
            $child = $this->addChild($source->getName());
        } else {
            $child = $this->addChild($source->getName(), $this->xmlentities($source));
        }

        $child->setParent($this);

        $attributes = $source->attributes();
        foreach ($attributes as $key => $value) {
            $child->addAttribute($key, $this->xmlentities($value));
        }

        foreach ($source->children() as $sourceChild) {
            $child->appendChild($sourceChild);
        }

        return $this;
    }

    /**
     * Extends current node with xml from $source.
     *
     * If $overwrite is false will merge only missing nodes
     * Otherwise will overwrite existing nodes
     *
     * @param \Magento\Framework\Simplexml\Element $source
     * @param boolean                              $overwrite
     *
     * @return \Magento\Framework\Simplexml\Element
     */
    public function extend($source, $overwrite = false)
    {
        if (!$source instanceof Element) {
            return $this;
        }

        foreach ($source->children() as $child) {
            $this->extendChild($child, $overwrite);
        }

        return $this;
    }

    /**
     * Extends one node.
     *
     * @param \Magento\Framework\Simplexml\Element $source
     * @param boolean                              $overwrite
     *
     * @return \Magento\Framework\Simplexml\Element
     */
    public function extendChild($source, $overwrite = false)
    {
        // this will be our new target node
        $targetChild = null;

        // name of the source node
        $sourceName = $source->getName();

        // here we have children of our source node
        $sourceChildren = $source->children();

        /* @var Element $targetChild */
        if (!$source->hasChildren()) {
            // handle string node
            if (isset($this->{$sourceName})) {
                // if target already has children return without regard
                if ($this->{$sourceName}->children()) {
                    return $this;
                }
                if ($overwrite) {
                    unset($this->{$sourceName});
                } else {
                    return $this;
                }
            }
            $targetChild = $this->addChild($sourceName, $source->xmlentities());
            $targetChild->setParent($this);
            foreach ($source->attributes() as $key => $value) {
                $targetChild->addAttribute($key, $this->xmlentities($value));
            }

            return $this;
        }
        if (isset($this->{$sourceName})) {
            $targetChild = $this->{$sourceName};
        }
        if (is_null($targetChild)) {
            // if child target is not found create new and descend
            $targetChild = $this->addChild($sourceName);
            $targetChild->setParent($this);
            foreach ($source->attributes() as $key => $value) {
                $targetChild->addAttribute($key, $this->xmlentities($value));
            }
        }
        // finally add our source node children to resulting new target node
        foreach ($sourceChildren as $childKey => $childNode) {
            $targetChild->extendChild($childNode, $overwrite);
        }

        return $this;
    }

    /**
     * Set node.
     *
     * @param string $path
     * @param string $value
     * @param bool   $overwrite
     *
     * @return \Magento\Framework\Simplexml\Element
     */
    public function setNode($path, $value, $overwrite = true)
    {
        $arr1 = explode('/', $path);
        $arr = [];
        foreach ($arr1 as $v) {
            if (!empty($v)) {
                $arr[] = $v;
            }
        }
        $last = sizeof($arr) - 1;
        $node = $this;
        foreach ($arr as $i => $nodeName) {
            if ($last === $i) {
                /*
                 * if (isset($node->$nodeName)) { if ($overwrite) { unset($node->$nodeName); } else { continue; } }
                 * $xml->addChild($nodeName, $node->xmlentities($value));
                 */
                if (!isset($node->{$nodeName}) || $overwrite) {
                    $node->{$nodeName} = $value;
                }
            } else {
                if (!isset($node->{$nodeName})) {
                    $node = $node->addChild($nodeName);
                } else {
                    $node = $node->{$nodeName};
                }
            }
        }

        return $this;
    }

    /**
     * Unset self from the XML-node tree.
     *
     * Note: trying to refer this object as a variable after "unsetting" like this will result in E_WARNING
     */
    public function unsetSelf()
    {
        $uniqueId = uniqid();
        $unqField = '__unique_id';
        $this[$unqField] = $uniqueId;
        $children = $this->getParent()->xpath('*');
        for ($i = count($children); $i > 0; $i--) {
            if ($children[$i - 1][0][$unqField] == $uniqueId) {
                unset($children[$i - 1][0]);

                return;
            }
        }
        unset($this[$unqField]);
    }

    /**
     * Returns parent node for the element.
     *
     * Currently using xpath
     *
     * @return \Magento\Framework\Simplexml\Element
     */
    public function getParent()
    {
        if (!empty($this->parentElement)) {
            $parent = $this->parentElement;
        } else {
            $arr = $this->xpath('..');

            // @todo: should we throw an exception here?
            if (!isset($arr[0])) {
                return $this;
            }

            return $arr[0];
        }
        return $parent;
    }
}
