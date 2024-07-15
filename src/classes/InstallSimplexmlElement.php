<?php

class InstallSimplexmlElement extends SimpleXMLElement {

    /**
     * Can add SimpleXMLElement values in XML tree
     *
     * @see SimpleXMLElement::addChild()
     *
     * @param string $name
     * @param null   $value
     * @param null   $namespace
     *
     * @return SimpleXMLElement
     */
    #[\ReturnTypeWillChange]
    public function addChild($name, $value = null, $namespace = null) {

        if ($value instanceof SimplexmlElement) {
            $content = trim((string) $value);

            if (strlen($content) > 0) {
                $newElement = parent::addChild($name, str_replace('&', '&amp;', $content), $namespace);
            } else {
                $newElement = parent::addChild($name);

                foreach ($value->attributes() as $k => $v) {
                    $newElement->addAttribute($k, $v);
                }

            }

            foreach ($value->children() as $child) {
                $newElement->addChild($child->getName(), $child);
            }

            return $newElement;
        } else {
            return parent::addChild($name, str_replace('&', '&amp;', $value), $namespace);
        }

    }

    /**
     * Generate nice and sweet XML
     *
     * @see   SimpleXMLElement::asXML()
     *
     * @since 1.0.0
     *
     * @param null $filename
     *
     * @return bool|mixed|string
     */
    #[\ReturnTypeWillChange]
    public function asXML($filename = null) {

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML(parent::asXML());

        if ($filename) {
            return (bool) file_put_contents($filename, $dom->saveXML());
        }

        return $dom->saveXML();
    }

}
