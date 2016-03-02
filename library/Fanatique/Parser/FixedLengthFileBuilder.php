<?php

namespace Fanatique\Parser;

use Closure;
use Fanatique\Helper\ObjectToArray;

/**
 * Class FixedLengthFileBuilder
 *
 * When given an object or array, and a mapping array, this builder wil generate a fixed-length file.
 *
 * @package Fanatique\Parser
 *
 */
class FixedLengthFileBuilder implements BuilderInterface
{

    /**
     * @var array Source that's going to be converted.
     */
    protected $source = null;

    /**
     * @var int Total length of each line.
     */
    protected $totalLength = 0;

    /**
     * @var string glue for the lines. Default NewLine
     */
    protected $glue = "\n";

    /**
     * array(
     *      'SourceKey' => array(
     *          'type' => 'string|date|source' // if source, the Key will be used to read the value from the source array
     *          'length' => int // required
     *          'format' => 'DateFormat|vsprintfstring' // If type is string or date, a format is required. Date is presumed to be the current datetime
     *          'value' => 'presetstring' // not required, if set, everything else but length is ignored and the value is added to the string.
     *          'args' => array( // Required for string. can be an empty array. Needs the correct arguments to match the vsprintf string
     *              'a',
     *              'b',
     *              'c',
     *          ),
     *      ),
     *      'SecondKey' => array() // Et cetera.
     * )
     * @var array Expects an array of arrays, mapping the keys, length, formatting and/or default value
     */
    protected $sourceMap = null;

    /**
     *
     * @var Closure
     */
    protected $callback = null;

    /**
     *
     * @var array
     */
    protected $content = array();


    /**
     * Build a fixed-length file from a given object or array.
     * @throws BuilderException
     */
    public function build()
    {
        // Check if we have a source. Break if failed.
        if (!isset($this->source)) {
            throw new BuilderException('No source specified', 255);
        }
        // Check if we have a map, otherwise it's going to be quite useless.
        if ((!$this->sourceMap)) {
            throw new BuilderException('No map specified', 255);
        }
        // Each item we have, is going to be looped by the linebuilder to create a line.
        foreach ($this->source as $key => $item) {
            $this->content[] = $this->buildLine($item);
        }

    }

    private function buildLine($lineSource)
    {
        $line = array();
        $totalLength = 0;
        // If we receive an Object to build, convert it to an array
        if (is_object($lineSource)) {
            $converter = new ObjectToArray();
            $convertedSource = $converter->obj2array($lineSource);
            $lineSource = $convertedSource['array'];
        }
        foreach ($this->getSourceMap() as $key => $value) {
            // Break if no length specified
            if (!array_key_exists('length', $value) || !is_int($value['length'])) {
                throw new BuilderException('Length not specified, exiting', 253);
            }

            $length = $value['length'];
            $totalLength += $length;

            $stringPart = $this->createStringPart($key, $value, $lineSource);
            // Create a string of the required length.
            $line[] = $this->createString($stringPart, $length);
        }
        if (!$this->checkLength($totalLength)) {
            throw new BuilderException('Error building fixed length file', 255);
        }
        return implode("", $line);

    }

    /**
     * @param string $key
     * @param array $value
     * @param array $source
     * @return bool|string
     */
    private function createStringPart($key, $value, $source)
    {
        $stringPart = '';
        /**
         * Check the options to build the line.
         */
        if (array_key_exists('value', $value)) { // Value is fixed
            $stringPart = $value['value'];
        } elseif (array_key_exists('type', $value) && $value['type'] === 'date') { // Dates are build on today's date
            $stringPart = date($value['format']);
        } elseif (array_key_exists('type', $value) && $value['type'] === 'string') { // Strings are formatted from arguments
            $args = isset($value['args']) ? $value['args'] : array();
            $stringPart = vsprintf($value['format'], $args);
        } elseif (array_key_exists('type', $value) && $value['type'] === 'source') { // Finally, if none of the above applies, we take the source and add it.
            $stringPart = $source[$key];
        }
        return $stringPart;
    }

    /**
     * @param string $value unpadded string
     * @param int $length Length of the part of the string.
     * @return string
     */
    private function createString($value, $length)
    {
        $line = str_pad($value, $length);
        $line = substr($line, 0, $length);
        return $line;
    }

    private function checkLength($length)
    {
        if ($this->totalLength === 0) {
            $this->totalLength = $length;
        } elseif ($length !== $this->totalLength) {
            return false;
        }
        return true;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return array
     */
    public function getSourceMap()
    {
        return $this->sourceMap;
    }

    /**
     * @param array $sourceMap
     */
    public function setSourceMap($sourceMap)
    {
        $this->sourceMap = $sourceMap;
    }

    /**
     * @return Closure
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param Closure $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return array
     */
    public function getContent()
    {
        return implode($this->glue, $this->content);
    }

    /**
     * @param array $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getUncombinedContent() {
        return $this->content;
    }

    /**
     * @return int
     */
    public function getTotalLength()
    {
        return $this->totalLength;
    }

    /**
     * @param int $totalLength
     */
    public function setTotalLength($totalLength)
    {
        $this->totalLength = $totalLength;
    }

    /**
     * @return string
     */
    public function getGlue()
    {
        return $this->glue;
    }

    /**
     * @param string $glue
     */
    public function setGlue($glue)
    {
        $this->glue = $glue;
    }

}