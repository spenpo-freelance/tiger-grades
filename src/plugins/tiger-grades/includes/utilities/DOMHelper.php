<?php
namespace Spenpo\TigerGrades\Utilities;

use DOMDocument;
use DOMElement;

/**
 * Helper class for DOM manipulation operations.
 * 
 * @package Spenpo\TigerGrades\Utilities
 * @since 0.0.5
 */
class DOMHelper {
    /**
     * Creates a new DOM element with specified attributes.
     * 
     * @param DOMDocument $dom       The DOM document instance
     * @param string      $tag       HTML tag name
     * @param string      $class     CSS class name
     * @param string|null $id        Optional element ID
     * @param string|null $text      Optional text content
     * @param array       $attributes Optional additional attributes
     * 
     * @return DOMElement The created element
     */
    public static function createElement(DOMDocument $dom, $tag, $class, $id = null, $text = null, $attributes = []) {
        $element = $dom->createElement($tag);
        $element->setAttribute('class', $class);
        
        if ($id) {
            $element->setAttribute('id', $class."-$id");
        }

        foreach ($attributes as $key => $value) {
            $element->setAttribute($key, $value);
        }
        
        if ($text !== null) {
            $element_text = $dom->createTextNode($text);
            $element->appendChild($element_text);
        }

        return $element;
    }
} 