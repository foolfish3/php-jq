<?php

/*
MIT License

Copyright (c) 2020 qian.yu

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

// diff from jquery
// if no param pass to css, will return all style as key-value map
// if no param pass to attr, will return all attributes as key-value map
// second param of find is $include_self

// category/manipulation/general-attributes
// .addClass
// .removeClass
// .hasClass
// .toggleClass
// .attr
// .removeAttr
// .val
// category/manipulation/style-properties
// .css
// category/miscellaneous/collection-manipulation
// .each
// category/effects
// .show
// .hide
// .toggle
// category/manipulation/copying
// .clone
// category/manipulation/dom-insertion-inside
// .html
// .text
// .append
// .appendTo
// .prepend
// .prependTo
// category/manipulation/dom-insertion-outside
// .after
// .insertAfter
// .before
// .insertBefore
// category/manipulation/dom-removal
// .empty
// .remove
// category/manipulation/dom-replacement
// .replaceAll
// .replaceWith
// category/traversing/filtering
// .eq
// .even
// .odd
// .first
// .last
// .filter
// .not
// .slice
// category/miscellaneous/dom-element-methods
// .get
// .index
// .size
// .toArray
// category/traversing/miscellaneous-traversal
// .add
// .contents
// category/traversing/tree-traversal
// .parent
// .children
// .next
// .prev

class JQ implements \IteratorAggregate, \Countable
{
    protected static $inited = false;
    protected static $document;
    protected static $xpath;
    protected $jq = array();
    //hold the root element, prevent from gc
    protected $jq_root = array();

    /**
     * get ownerDocument of all node created by \JQ.
     * normally you don't need it, except for some time, for example you want create node yourself.
     * don't append any child to document tree.
     * @return \DOMDocument
     */
    public static function jq_document()
    {
        self::init();
        return self::$document;
    }

    /**
     * do nothing, just let you to autoload class file then you can use jq() function.
     * @return true
     */
    public static function init()
    {
        if (!self::$inited) {
            list(self::$document) = self::createDocument();
            self::$xpath = new \DOMXPath(self::$document);
            self::$inited = true;
        }
        return true;
    }

    protected static function getRoot($node)
    {
        for (; $node->parentNode;) {
            $node = $node->parentNode;
        }
        return $node;
    }

    protected static function createDocument($html = "")
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML("<!DOCTYPE html><html><head><meta charset=\"UTF-8\"></head><body>$html</body></html>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        return array($doc, $doc->documentElement->childNodes->item(1));
    }

    protected static function loadHTMLPart($html)
    {
        //echo $html;
        list($doc, $body) = self::createDocument($html);
        $ar = array();
        foreach ($body->childNodes as $node) {
            $ar[] = self::$document->importNode($node, true);
        }
        return $ar;
    }

    protected static function getOuterHTML($node)
    {
        return $node->ownerDocument->saveHTML($node);
    }

    protected static function getInnerHTML($node)
    {
        list($doc, $body) = self::createDocument();
        foreach ($node->childNodes as $child) {
            $body->appendChild($doc->importNode($child, true));
        }
        \preg_match("{<body>(.*)</body>}s", $doc->saveHTML($body), $m);
        return $m[1];
    }

    protected static function setAttribute($node, $name, $value)
    {
        if ($value === NULL) {
            $node->removeAttribute($name);
        } else {
            $node->setAttribute($name, $value);
        }
    }

    protected static function getAttribute($node, $name)
    {
        return $node->getAttribute($name);
    }

    protected static function setTextContent($node, $textContent)
    {
        $node->textContent = $textContent;
    }

    protected static function getTextContent($node)
    {
        return $node->textContent;
    }

    protected static function removeAllChild($node)
    {
        for (; $node->hasChildNodes();) {
            $node->removeChild($node->firstChild);
        }
        return true;
    }

    protected static function parseCss($str, $k = NULL, $v = NULL)
    {
        $map = array();
        foreach (\explode(";", $str) as $line) {
            if (!\preg_match("{^([^\\:]+)(?:\\:(.*))$}s", trim($line), $m)) {
                continue;
            }
            $map[\trim($m[1])] = \trim($m[2]);
        }
        if ($k !== NULL) {
            if ($v === NULL || \trim($v) === "") {
                unset($map[$k]);
            } else {
                $map[$k] = \trim($v);
            }
        }
        return $map;
    }

    protected static function parseClasses($classes)
    {
        if (\is_array($classes)) {
            $classes = \implode(" ", $classes);
        }
        $map = array();
        foreach (\preg_split("{\s+}", $classes) as $cls) {
            if ($cls !== "") {
                $map[$cls] = $cls;
            }
        }
        return $map;
    }

    protected static function parseAddClasses($classes, $classes2 = "")
    {
        $map = self::parseClasses($classes);
        foreach (self::parseClasses($classes2) as $cls) {
            $map[$cls] = $cls;
        }
        return $map;
    }

    protected static function parseToggleClasses($classes, $classes2, $state)
    {
        $map = self::parseClasses($classes);
        foreach (self::parseClasses($classes2) as $cls) {
            if (isset($map[$cls])) {
                if (!$state || $state === NULL) {
                    unset($map[$cls]);
                }
            } else {
                if ($state || $state === NULL) {
                    $map[$cls] = 1;
                }
            }
        }
        return $map;
    }

    protected static function parseRemoveClasses($classes, $classes2 = "")
    {
        $map = self::parseClasses($classes);
        foreach (self::parseClasses($classes2) as $cls) {
            unset($map[$cls]);
        }
        return $map;
    }

    protected function addone($jq)
    {
        if ($jq instanceof \DOMNode) {
            if (!$jq->ownerDocument->isSameNode(self::$document)) {
                throw new \ErrorException("not same document");
            }
            $this->jq[] = $jq;
            $this->jq_root[] = self::getRoot($jq);
        } elseif (\is_array($jq) || $jq instanceof \Traversable) {
            foreach ($jq as $jq2) {
                $this->addone($jq2);
            }
        } elseif (\is_scalar($jq)) {
            foreach (self::loadHTMLPart($jq) as $node) {
                $this->jq[] = $node;
            }
        } elseif ($jq === NULL) {
        } else {
            throw new \ErrorException("unknown param type");
        }
    }

    /**
     * thanks to https://github.com/tj/php-selector 
     * @param string $selector
     * @return string
     */
    // --- Selector.inc - (c) Copyright TJ Holowaychuk <tj@vision-media.ca> MIT Licensed
    public static function selector_to_xpath($selector, $include_self = true)
    {
        // remove spaces around operators
        $selector = \preg_replace('/\s*>\s*/', '>', $selector);
        $selector = \preg_replace('/\s*~\s*/', '~', $selector);
        $selector = \preg_replace('/\s*\+\s*/', '+', $selector);
        $selector = \preg_replace('/\s*,\s*/', ',', $selector);
        $selectors = \preg_split('/\s+(?![^\[]+\])/', $selector);

        foreach ($selectors as &$selector) {
            // ,
            $selector = \preg_replace('/,/', '|descendant-or-self::', $selector);
            // input:checked, :disabled, etc.
            $selector = \preg_replace('/(.+)?:(checked|disabled|required|autofocus)/', '\1[@\2="\2"]', $selector);
            // input:autocomplete, :autocomplete
            $selector = \preg_replace('/(.+)?:(autocomplete)/', '\1[@\2="on"]', $selector);
            // input:button, input:submit, etc.
            $selector = \preg_replace('/:(text|password|checkbox|radio|button|submit|reset|file|hidden|image|datetime|datetime-local|date|month|time|week|number|range|email|url|search|tel|color)/', 'input[@type="\1"]', $selector);
            // foo[id]
            $selector = \preg_replace('/(\w+)\[([_\w-]+[_\w\d-]*)\]/', '\1[@\2]', $selector);
            // [id]
            $selector = \preg_replace('/\[([_\w-]+[_\w\d-]*)\]/', '*[@\1]', $selector);
            // foo[id=foo]
            $selector = \preg_replace('/\[([_\w-]+[_\w\d-]*)=[\'"]?(.*?)[\'"]?\]/', '[@\1="\2"]', $selector);
            // [id=foo]
            $selector = \preg_replace('/^\[/', '*[', $selector);
            // div#foo
            $selector = \preg_replace('/([_\w-]+[_\w\d-]*)\#([_\w-]+[_\w\d-]*)/', '\1[@id="\2"]', $selector);
            // #foo
            $selector = \preg_replace('/\#([_\w-]+[_\w\d-]*)/', '*[@id="\1"]', $selector);
            // div.foo
            $selector = \preg_replace('/([_\w-]+[_\w\d-]*)\.([_\w-]+[_\w\d-]*)/', '\1[contains(concat(" ",@class," ")," \2 ")]', $selector);
            // .foo
            $selector = \preg_replace('/\.([_\w-]+[_\w\d-]*)/', '*[contains(concat(" ",@class," ")," \1 ")]', $selector);
            // div:first-child
            $selector = \preg_replace('/([_\w-]+[_\w\d-]*):first-child/', '*/\1[position()=1]', $selector);
            // div:last-child
            $selector = \preg_replace('/([_\w-]+[_\w\d-]*):last-child/', '*/\1[position()=last()]', $selector);
            // :first-child
            $selector = \str_replace(':first-child', '*/*[position()=1]', $selector);
            // :last-child
            $selector = \str_replace(':last-child', '*/*[position()=last()]', $selector);
            // :nth-last-child
            $selector = \preg_replace('/:nth-last-child\((\d+)\)/', '[position()=(last() - (\1 - 1))]', $selector);
            // div:nth-child
            $selector = \preg_replace('/([_\w-]+[_\w\d-]*):nth-child\((\d+)\)/', '*/*[position()=\2 and self::\1]', $selector);
            // :nth-child
            $selector = \preg_replace('/:nth-child\((\d+)\)/', '*/*[position()=\1]', $selector);
            // :contains(Foo)
            $selector = \preg_replace('/([_\w-]+[_\w\d-]*):contains\((.*?)\)/', '\1[contains(string(.),"\2")]', $selector);
            // >
            $selector = \preg_replace('/>/', '/', $selector);
            // ~
            $selector = \preg_replace('/~/', '/following-sibling::', $selector);
            // +
            $selector = \preg_replace('/\+([_\w-]+[_\w\d-]*)/', '/following-sibling::\1[position()=1]', $selector);
            $selector = \str_replace(']*', ']', $selector);
            $selector = \str_replace(']/*', ']', $selector);
        }

        // ' '
        $selector = \implode('/descendant::', $selectors);
        if ($include_self) {
            $selector = 'descendant-or-self::' . $selector;
        } else {
            $selector = 'descendant::' . $selector;
        }

        // :scope
        $selector = \preg_replace('/(((\|)?descendant-or-self::):scope)/', '.\3', $selector);
        // $element
        $sub_selectors = \explode(',', $selector);

        foreach ($sub_selectors as $key => $sub_selector) {
            $parts = \explode('$', $sub_selector);
            $sub_selector = \array_shift($parts);

            if (\count($parts) && \preg_match_all('/((?:[^\/]*\/?\/?)|$)/', $parts[0], $matches)) {
                $results = $matches[0];
                $results[] = \str_repeat('/..', \count($results) - 2);
                $sub_selector .= \implode('', $results);
            }

            $sub_selectors[$key] = $sub_selector;
        }

        $selector = \implode(',', $sub_selectors);

        return $selector;
    }

    /**
     * @return \JQ
     */
    public function addClass($classes)
    {
        foreach ($this->jq as $node) {
            $ss = self::parseAddClasses($node->getAttribute("class"), $classes);
            self::setAttribute($node, "class", $ss ? \implode(" ", $ss) : NULL);
        }
        return $this;
    }
    /**
     * @return \JQ
     */
    public function removeClass($classes)
    {
        foreach ($this->jq as $node) {
            $ss = self::parseRemoveClasses($node->getAttribute("class"), $classes);
            self::setAttribute($node, "class", $ss ? \implode(" ", $ss) : NULL);
        }
        return $this;
    }
    /**
     * @return bool
     */
    public function hasClass($class)
    {
        $class = \strval($class);
        foreach ($this->jq as $node) {
            $map = self::parseClasses(self::getAttribute($node, "class"));
            if (isset($map[$class]) || $class === "") {
                return true;
            }
        }
        return false;
    }
    /**
     * @return \JQ
     */
    public function toggleClass($classes, $state = NULL)
    {
        foreach ($this->jq as $node) {
            $ss = self::parseToggleClasses($node->getAttribute("class"), $classes, $state);
            self::setAttribute($node, "class", $ss ? \implode(" ", $ss) : NULL);
        }
        return $this;
    }
    /**
     * @return \JQ|string|NULL
     */
    public function attr($name = NULL, $value = NULL)
    {
        if (\func_num_args() === 1) {
            if (\is_array($name)) {
                foreach ($name as $k => $v) {
                    $this->attr($k, $v);
                }
                return $this;
            } elseif (\is_string($name)) {
                foreach ($this->jq as $node) {
                    if (!$node instanceof \DOMElement) {
                        return NULL;
                    }
                    return self::getAttribute($node, $name);
                }
                return NULL;
            } else {
                throw new \ErrorException("unknown 1st param type");
            }
        } elseif (\func_num_args() === 2) {
            if (\is_string($name)) {
                foreach ($this->jq as $node) {
                    self::setAttribute($node, $name, $value);
                }
                return $this;
            } else {
                throw new \ErrorException("unknown 1st param type");
            }
        } elseif (\func_num_args() === 0) {
            foreach ($this->jq as $node) {
                if (!$node instanceof \DOMElement) {
                    return NULL;
                }
                $map = array();
                foreach ($node->attributes as $attr) {
                    $map[$attr->name] = $attr->value;
                }
                return $map;
            }
            return NULL;
        } else {
            throw new \ErrorException("args count should be 0 or 1 or 2");
        }
    }
    /**
     * @return \JQ
     */
    public function removeAttr($name)
    {
        return $this->attr($name, NULL);
    }
    /**
     * @return \JQ|string|NULL
     */
    public function val($value = NULL)
    {
        if (\func_num_args() === 0) {
            foreach ($this->jq as $node) {
                if (!$node instanceof \DOMElement) {
                    return NULL;
                }
                if ($node->tagName === "textarea") {
                    return self::getTextContent($node);
                } elseif ($node->tagName === "input" || $node->tagName === "option") {
                    $type = self::getAttribute($node, "type");
                    if ($type === "") {
                        return null;
                    }
                    return self::getAttribute($node, "value");
                } elseif ($node->tagName === "select") {
                    if (self::getAttribute($node, "multiple") === "multiple") {
                        $ar = array();
                        foreach ($node->childNodes as $child) {
                            if (!$child instanceof \DOMElement) {
                                continue;
                            }
                            if ($child->tagName === "option") {
                                if (self::getAttribute($child, "selected") === "selected") {
                                    $ar[] = self::getAttribute($child, "value");
                                }
                            } elseif ($child->tagName === "optgroup") {
                                foreach ($child->childNodes as $child) {
                                    if (!$child instanceof \DOMElement || $child->tagName !== "option") {
                                        continue;
                                    }
                                    if (self::getAttribute($child, "selected") === "selected") {
                                        $ar[] = self::getAttribute($child, "value");
                                    }
                                }
                            }
                        }
                        return $ar;
                    } else {
                        foreach ($node->childNodes as $child) {
                            if (!$child instanceof \DOMElement) {
                                continue;
                            }
                            if ($child->tagName === "option") {
                                if (self::getAttribute($child, "selected") === "selected") {
                                    return self::getAttribute($child, "value");
                                }
                            } elseif ($child->tagName === "optgroup") {
                                foreach ($child->childNodes as $child) {
                                    if (!$child instanceof \DOMElement || $child->tagName !== "option") {
                                        continue;
                                    }
                                    if (self::getAttribute($child, "selected") === "selected") {
                                        return self::getAttribute($child, "value");
                                    }
                                }
                            }
                        }
                    }
                }
                return NULL;
            }
            return NULL;
        }
        if ($value === NULL) {
            return $this;
        }
        $value = \trim($value);
        foreach ($this->jq as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }
            if ($node->tagName === "textarea") {
                self::setTextContent($node, $value);
            } elseif ($node->tagName === "input" || $node->tagName === "option") {
                self::setAttribute($node, "value", $value);
            } elseif ($node->tagName === "select") {
                foreach ($node->childNodes as $child) {
                    if (!$child instanceof \DOMElement) {
                        continue;
                    }
                    if ($child->tagName === "option") {
                        if (self::getAttribute($child, "value") === $value) {
                            self::setAttribute($child, "selected", "selected");
                        } else {
                            self::setAttribute($child, "selected", NULL);
                        }
                    } elseif ($child->tagName === "optgroup") {
                        foreach ($child->childNodes as $child) {
                            if (!$child instanceof \DOMElement || $child->tagName !== "option") {
                                continue;
                            }
                            if (self::getAttribute($child, "value") === $value) {
                                self::setAttribute($child, "selected", "selected");
                            } else {
                                self::setAttribute($child, "selected", NULL);
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }
    /**
     * @return \JQ|string|NULL
     */
    public function css($name = NULL, $value = NULL)
    {
        if (\func_num_args() === 1) {
            if (\is_array($name)) {
                foreach ($name as $k => $v) {
                    $this->css($k, $v);
                }
                return $this;
            } elseif (\is_string($name)) {
                foreach ($this->jq as $node) {
                    if (!$node instanceof \DOMElement) {
                        return NULL;
                    }
                    $map = self::parseCss(self::getAttribute($node, "style"));
                    return @$map[$name];
                }
                return NULL;
            } else {
                throw new \ErrorException("unknown 1st param type");
            }
        } elseif (\func_num_args() === 2) {
            if (\is_string($name)) {
                foreach ($this->jq as $node) {
                    $ss = array();
                    foreach (self::parseCss(self::getAttribute($this->jq[0], "style"), $name, $value) as $k => $v) {
                        $ss[] = "$k:$v;";
                    }
                    self::setAttribute($node, "style", $ss ? \implode($ss) : NULL);
                }
                return $this;
            } else {
                throw new \ErrorException("unknown 1st param type");
            }
        } elseif (\func_num_args() === 0) {
            foreach ($this->jq as $node) {
                if (!$node instanceof \DOMElement) {
                    return NULL;
                }
                return self::parseCss(self::getAttribute($node, "style"));
            }
            return NULL;
        } else {
            throw new \ErrorException("args count should be 0 or 1 or 2");
        }
    }
    /**
     * @return \JQ
     */
    public function each($func)
    {
        foreach ($this->jq as $k => $node) {
            $func($k, $node);
        }
        return $this;
    }
    /**
     * @return \JQ
     */
    public function show()
    {
        foreach ($this->jq as $k => $node) {
            $map = self::parseCss(self::getAttribute($this->jq[0], "style"));
            if (@$map["display"] === "none") {
                if (isset($map["display-old"])) {
                    $map["display"] = $map["display-old"];
                    unset($map["display-old"]);
                }
                $ss = array();
                foreach ($map as $k => $v) {
                    $ss[] = "$k:$v;";
                }
                self::setAttribute($node, "style", $ss ? \implode($ss) : NULL);
            }
        }
        return $this;
    }
    /**
     * @return \JQ
     */
    public function hide()
    {
        foreach ($this->jq as $k => $node) {
            $map = self::parseCss(self::getAttribute($this->jq[0], "style"));
            if (@$map["display"] !== "none") {
                if (isset($map["display"])) {
                    $map["display-old"] = $map["display"];
                }
                $map["display"] = "none";
                $ss = array();
                foreach ($map as $k => $v) {
                    $ss[] = "$k:$v;";
                }
                self::setAttribute($node, "style", $ss ? \implode($ss) : NULL);
            }
        }
        return $this;
    }
    /**
     * @return \JQ
     */
    public function toggle()
    {
        foreach ($this->jq as $k => $node) {
            $map = self::parseCss(self::getAttribute($this->jq[0], "style"));
            if (@$map["display"] !== "none") {
                if (isset($map["display"])) {
                    $map["display-old"] = $map["display"];
                }
                $map["display"] = "none";
                $ss = array();
                foreach ($map as $k => $v) {
                    $ss[] = "$k:$v;";
                }
                self::setAttribute($node, "style", $ss ? \implode($ss) : NULL);
            } else {
                if (isset($map["display-old"])) {
                    $map["display"] = $map["display-old"];
                    unset($map["display-old"]);
                }
                $ss = array();
                foreach ($map as $k => $v) {
                    $ss[] = "$k:$v;";
                }
                self::setAttribute($node, "style", $ss ? \implode($ss) : NULL);
            }
        }
        return $this;
    }
    /**
     * @return \JQ
     */
    public function clone()
    {
        $ar = array();
        foreach ($this->jq as $node) {
            $ar[] = $node->cloneNode(true);
        }
        return new self($ar);
    }
    /**
     * @return \JQ|string|NULL
     */
    public function html($innerHTML = NULL)
    {
        if (\func_num_args() === 0) {
            return $this->innerHTML();
        } else {
            if (!is_scalar($innerHTML)) {
                throw new \ErrorException("1st param should be string");
            }
            foreach ($this->jq as $node) {
                self::removeAllChild($node);
                foreach (self::loadHTMLPart($innerHTML) as $nd) {
                    $node->appendChild($nd);
                }
            }
            return $this;
        }
    }
    /**
     * @return \JQ|string|NULL
     */
    public function text($text = NULL)
    {
        if (\func_num_args() === 0) {
            return $this->jq ? self::getTextContent($this->jq[0]) : NULL;
        } else {
            if (!is_scalar($text)) {
                throw new \ErrorException("1st param should be string");
            }
            foreach ($this->jq as $node) {
                self::removeAllChild($node);
                self::setTextContent($node, $text);
            }
            return $this;
        }
    }
    /**
     * @return \JQ
     */
    public function append($content)
    {
        foreach ($this->jq as $node) {
            foreach (new self(func_get_args()) as $child) {
                $node->appendChild($child);
            }
        }
        return $this;
    }
    /**
     * @return \JQ
     */
    public function appendTo($target)
    {
        $jq = new self($target);
        $jq->append($this);
        return $this;
    }
    /**
     * @return \JQ
     */
    public function prepend($content)
    {
        foreach ($this->jq as $node) {
            foreach (new self(func_get_args()) as $child) {
                $node->insertBefore($child, $node->firstChild);
            }
        }
        return $this;
    }
    /**
     * @return \JQ
     */
    public function prependTo($target)
    {
        $jq = new self($target);
        $jq->prepend($this);
        return $this;
    }
    /**
     * @return \JQ
     */
    public function after($content)
    {
        foreach ($this->jq as $node) {
            if ($node->parentNode === NULL) {
                continue;
            }
            $parentNode = $node->parentNode;
            $nextSibling = $node->nextSibling;
            foreach (new self(func_get_args()) as $child) {
                $parentNode->insertBefore($child, $nextSibling);
            }
        }
        return $this;
    }
    /**
     * @return \JQ
     */
    public function insertAfter($target)
    {
        $jq = new self($target);
        $jq->after($this);
        return $this;
    }
    /**
     * @return \JQ
     */
    public function before($content)
    {
        foreach ($this->jq as $node) {
            if ($node->parentNode === NULL) {
                continue;
            }
            $parentNode = $node->parentNode;
            foreach (new self(func_get_args()) as $child) {
                $parentNode->insertBefore($child, $node);
            }
        }
        return $this;
    }
    /**
     * @return \JQ
     */
    public function insertBefore($target)
    {
        $jq = new self($target);
        $jq->before($this);
        return $this;
    }
    /**
     * @return \JQ
     */
    public function empty()
    {
        foreach ($this->jq as $node) {
            self::removeAllChild($node);
        }
        return $this;
    }
    /**
     * @return \JQ
     */
    public function remove()
    {
        foreach ($this->jq as $node) {
            if ($node->parentNode === NULL) {
                continue;
            }
            $node->parentNode->removeChild($node);
        }
        return $this;
    }
    /**
     * @return \JQ
     */
    public function replaceAll($target)
    {
        $jq = new self($target);
        $jq->replaceWith($this);
        return $this;
    }
    /**
     * @return \JQ
     */
    public function replaceWith($content)
    {
        foreach ($this->jq as $node) {
            if ($node->parentNode === NULL) {
                continue;
            }
            $parentNode = $node->parentNode;
            foreach (new self(func_get_args()) as $child) {
                $parentNode->insertBefore($child, $node);
            }
            $parentNode->removeChild($node);
        }
        return $this;
    }
    /**
     * @return \JQ
     */
    public function eq($index)
    {
        return new self(\array_slice($this->jq, $index, 1));
    }
    /**
     * @return \JQ
     */
    public function even()
    {
        $ar = array();
        foreach ($this->jq as $k => $node) {
            if ($k % 2 === 0) {
                $ar[] = $node;
            }
        }
        return new self($ar);
    }
    /**
     * @return \JQ
     */
    public function odd()
    {
        $ar = array();
        foreach ($this->jq as $k => $node) {
            if ($k % 2 === 1) {
                $ar[] = $node;
            }
        }
        return new self($ar);
    }
    /**
     * @return \JQ
     */
    public function first()
    {
        return $this->eq(0);
    }
    /**
     * @return \JQ
     */
    public function last()
    {
        return $this->eq(-1);
    }
    /**
     * @return \JQ
     */
    public function filter($elements)
    {
        $ar = array();
        $elements = new self($elements);
        foreach ($this->jq as $node) {
            foreach ($elements as $element) {
                if ($node->isSameNode($element)) {
                    $ar[] = $node;
                    continue 2;
                }
            }
        }
        return new self($ar);
    }

    /**
     * @return \JQ
     */
    public function not($elements)
    {
        $ar = array();
        $elements = new self($elements);
        foreach ($this->jq as $node) {
            foreach ($elements as $element) {
                if ($node->isSameNode($element)) {
                    continue 2;
                }
            }
            $ar[] = $node;
        }
        return new self($ar);
    }
    /**
     * @return \JQ
     */
    public function slice($start, $end = NULL)
    {
        $length = \count($this->jq);
        if ($start < 0) {
            $start = $length + $start;
        }
        if ($end === NULL) {
            $end = $length;
        } elseif ($end < 0) {
            $end = $length + $end;
        }
        $ar = array();
        foreach ($this->jq as $k => $node) {
            if ($k % 2 === 1) {
                $ar[] = $node;
            }
        }
        return new self(array_slice($this->jq, $start, $end - $start));
    }
    /**
     * @return \DOMNode|NULL
     */
    public function get($index = NULL)
    {
        if (\func_num_args() === 0) {
            return $this->jq;
        } else {
            return @$this->jq[$index];
        }
    }
    /**
     * @return int return -1 if not found or other error
     */
    public function index($element = NULL)
    {
        if (\func_num_args() === 0) {
            foreach ($this->jq as $node) {
                if ($node->parentNode === NULL) {
                    return -1;
                }
                $index = -1;
                foreach ($node->parentNode->childNodes as $child) {
                    if ($child instanceof \DOMElement) {
                        $index++;
                        if ($node->isSameNode($child)) {
                            return $index;
                        }
                    }
                }
                throw new \ErrorException("BUG");
            }
            return -1;
        } else {
            $element = (new self($element))->get(0);
            if ($element === NULL) {
                return -1;
            }
            foreach ($this->jq as $k => $node) {
                if ($node->isSameNode($element)) {
                    return $k;
                }
            }
            return -1;
        }
    }
    /**
     * @return int
     */
    public function size()
    {
        return \count($this->jq);
    }
    /**
     * @return array
     */
    public function toArray()
    {
        return $this->jq;
    }
    /**
     * @return \JQ
     */
    public function add($elements)
    {
        return new self($this, $elements);
    }
    /**
     * @return \JQ
     */
    public function contents()
    {
        $ar = array();
        foreach ($this->jq as $node) {
            foreach ($node->childNodes as $child) {
                $ar[] = $child;
            }
        }
        return new self($ar);
    }
    /**
     * @return \JQ
     */
    public function parent()
    {
        $ar = array();
        foreach ($this->jq as $node) {
            $ar[] = $node->parentNode;
        }
        return new self($ar);
    }
    /**
     * @return \JQ
     */
    public function children()
    {
        $ar = array();
        foreach ($this->jq as $node) {
            foreach ($node->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $ar[] = $child;
                }
            }
        }
        return new self($ar);
    }
    /**
     * @return \JQ
     */
    public function parents()
    {
        $ar = array();
        foreach ($this->jq as $node) {
            for (; $node->parentNode !== NULL; $node = $node->parentNode) {
                $ar[] = $node->parentNode;
            }
        }
        return new self($ar);
    }
    /**
     * @return \JQ
     */
    public function next()
    {
        $ar = array();
        foreach ($this->jq as $node) {
            for (; $node->nextSibling !== NULL;) {
                $node = $node->nextSibling;
                if ($node instanceof \DOMElement) {
                    $ar[] = $node;
                    break;
                }
            }
        }
        return new self($ar);
    }
    /**
     * @return \JQ
     */
    public function prev()
    {
        $ar = array();
        foreach ($this->jq as $node) {
            for (; $node->previousSibling !== NULL;) {
                $node = $node->previousSibling;
                if ($node instanceof \DOMElement) {
                    $ar[] = $node;
                    break;
                }
            }
        }
        return new self($ar);

        $ar = array();
        foreach ($this->jq as $node) {
            $ar[] = $node->previousSibling;
        }
        return new self($ar);
    }

    /**
     * equivalent to DOMXPath::evaluate.
     * @return mixed
     */
    public function evaluate($expression)
    {
        foreach ($this->jq as $node) {
            return self::$xpath->evaluate($expression, $node);
        }
        return NULL;
    }

    /**
     * equivalent to DOMXPath::query.
     * @return \JQ
     */
    public function query($expression)
    {
        $ar = array();
        foreach ($this->jq as $node) {
            $ar[] = self::$xpath->query($expression, $node);
        }
        return new self($ar);
    }
    /**
     * @return \JQ
     */
    public function find($selector, $include_self = false)
    {
        $expression = self::selector_to_xpath($selector, $include_self);
        $ar = array();
        foreach ($this->jq as $node) {
            $ar[] = self::$xpath->query($expression, $node);
        }
        return new self($ar);
    }

    public function outerHTML()
    {
        return $this->jq ? self::getOuterHTML($this->jq[0]) : NULL;
    }

    public function innerHTML()
    {
        return $this->jq ? self::getInnerHTML($this->jq[0]) : NULL;
    }

    public function __construct()
    {
        self::init();
        $this->addone(\func_get_args());
    }

    /**
     * implement Countable, support count()
     * @return int
     */
    public function count()
    {
        return \count($this->jq);
    }

    /**
     * implement IteratorAggregate, support foreach
     * @return Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->jq);
    }

    /**
     * return outerHTML, if it's a <html> node
     * @return \JQ
     */
    public function __toString()
    {
        $ss = array();
        foreach ($this->jq as $child) {
            $ss[] = self::getOuterHTML($child);
        }
        return \implode($ss);
    }

    /**
     * shortcut for new JQ().
     * @return \JQ
     */
    public static function jq()
    {
        return new self(\func_get_args());
    }

    /**
     * equivalent to DOMDocument::createTextNode
     * @param string $text
     * @return \JQ
     */
    public static function jq_text($text)
    {
        return new self(self::jq_document()->createTextNode($text));
    }

    /**
     * equivalent to DOMDocument::createComment
     * @param string $text
     * @return \JQ
     */
    public static function jq_comment($text)
    {
        return new self(self::jq_document()->createComment($text));
    }

    /**
     * equivalent to DOMDocument::createCDATASection
     * @param string $text
     * @return \JQ
     */
    public static function jq_cdata($text)
    {
        return new self(self::jq_document()->createCDATASection($text));
    }

    /**
     * create JQ from full html string, new JQ() cannot create full html doc or body element, use this function instead.
     * equivalent to DOMDocument::loadHTML
     * @param string $text full html document string 
     * @return \JQ
     */
    public static function jq_load_html($full_html_str)
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML($full_html_str);
        return new self(self::jq_document()->importNode($doc->documentElement, true));
    }

    /**
     * create JQ from full html string, new JQ() cannot create full html doc or body element, use this function instead.
     * equivalent to DOMDocument::loadHTML
     * @param string $path file path or url, will pass to file_get_contents
     * @return \JQ
     */
    public static function jq_load_html_file($path)
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML(\file_get_contents($path));
        return new self(self::jq_document()->importNode($doc->documentElement, true));
    }


    /**
     * equivalent to DOMDocument::importNode
     * @return \JQ
     */
    public static function jq_import_node(\DOMNode $node)
    {
        return new self(self::jq_document()->importNode($node, true));
    }

    /**
     * return new JQ() from ob_get_contents().
     * @param bool $trim remove blank before first '<' and after last '>'
     * @return \JQ
     */
    public static function jq_ob_get_contents($trim = true)
    {
        $content = \ob_get_contents();
        return new self($content);
    }

    /**
     * return new JQ() from ob_get_contents(), automatic close ob with ob_end_clean().
     * @param bool $trim remove blank before first '<' and after last '>'
     * @return \JQ
     */
    public static function jq_ob_end_clean($trim = true)
    {
        $content = \ob_get_contents();
        \ob_end_clean();
        return new self($content);
    }

    /**
     * remove blank before first '<' and after last '>'
     * @param string $content
     * @param bool $trim if false, just return original $content
     * @return string
     */
    public static function trim($content, $trim = true)
    {
        if ($trim) {
            $s = \trim($content);
            if (\substr($s, 0, 1) === "<" && \substr($s, -1, 1) === ">") {
                return $s;
            }
        }
        return $content;
    }
}

/**
 * shortcut for new JQ().
 * @return \JQ
 */
function jq()
{
    return JQ::jq(\func_get_args());
}

/**
 * get ownerDocument of all node created by \JQ.
 * normally you don't need it, except for some time, for example you want create node yourself.
 * don't append any child to document tree.
 * @return \DOMDocument
 */
function jq_document()
{
    return JQ::jq_document();
}

/**
 * equivalent to DOMDocument::createTextNode
 * @param string $text
 * @return \JQ
 */
function jq_text($text)
{
    return JQ::jq_text($text);
}

/**
 * equivalent to DOMDocument::createComment
 * @param string $text
 * @return \JQ
 */
function jq_comment($text)
{
    return JQ::jq_comment($text);
}

/**
 * equivalent to DOMDocument::createCDATASection
 * @param string $text
 * @return \JQ
 */
function jq_cdata($text)
{
    return JQ::jq_cdata($text);
}

/**
 * create JQ from full html string, new JQ() cannot create full html doc or body element, use this function instead.
 * equivalent to DOMDocument::loadHTML
 * @param string $text full html document string 
 * @return \JQ
 */
function jq_load_html($full_html_str)
{
    return JQ::jq_load_html($full_html_str);
}

/**
 * create JQ from full html string, new JQ() cannot create full html doc or body element, use this function instead.
 * equivalent to DOMDocument::loadHTML
 * @param string $path file path or url, will pass to file_get_contents
 * @return \JQ
 */
function jq_load_html_file($full_html_str)
{
    return JQ::jq_load_html_file($full_html_str);
}

/**
 * equivalent to DOMDocument::importNode
 * @return \JQ
 */
function jq_import_node(\DOMNode $node)
{
    return JQ::jq_import_node($node);
}

/**
 * return new JQ() from ob_get_contents().
 * @param bool $trim remove blank before first '<' and after last '>'
 * @return \JQ
 */
function jq_ob_get_contents($trim = true)
{
    return JQ::jq_ob_get_contents($trim);
}

/**
 * return new JQ() from ob_get_contents(), automatic close ob with ob_end_clean().
 * @param bool $trim remove blank before first '<' and after last '>'
 * @return \JQ
 */
function jq_ob_end_clean($trim = true)
{
    return JQ::jq_ob_end_clean($trim);
}

