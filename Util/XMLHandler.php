<?php

namespace Dnna\Payum\AlphaBank\Util;

use SimpleXMLElement;

class XMLHandler
{
    public function arrayToXml(array $data): SimpleXMLElement
    {
        $root = array_keys($data)[0];
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><' . $root . '></' . $root . '>');
        $this->toXml($xml, $data[$root]);

        return $xml;
    }

    private function toXml(SimpleXMLElement $object, array $data): void
    {
        foreach ($data as $key => $value) {
            if ($key == '_attributes') {
                foreach ($value as $curAttrKey => $curAttrValue) {
                    $object->addAttribute($curAttrKey, $curAttrValue);
                }
            } else {
                if (\is_array($value)) {
                    $newObject = $object->addChild($key);
                    $this->toXml($newObject, $value);
                } else {
                    $object->addChild($key, $value);
                }
            }
        }
    }
}
