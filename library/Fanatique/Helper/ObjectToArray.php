<?php

namespace Fanatique\Helper;

/**
 * Class ObjectToArray
 * Build a mapped array of an object so it can be used in the fixed length builder.
 * This is useful, because this way, we can use the FLB with both arrays as objects.
 * The objects will simply be build to a working array which FLB can use.
 *
 * Returns an array like this:
 * array(
 *      'SOURCE_KEYS' => array(
 *          '*array' => array(
 *                  'sourcekey1',
 *                  'sourcekey2'
 *          )
 *      ),
 *      'array' => array(
 *          'sourcekey1' => 'value1',
 *          'sourcekey2' => 'value2'
 *      )
 * );
 *
 * @package Fanatique\Helper
 */
class ObjectToArray
{

    public function obj2array ( &$Instance ) {
        $clone = (array) $Instance;
        $returnArray = array ();
        $returnArray['___SOURCE_KEYS_'] = $clone;

        while ( list ($key, $value) = each ($clone) ) {
            $aux = explode ("\0", $key);
            $newkey = $aux[count($aux)-1];
            $returnArray[$newkey] = &$returnArray['___SOURCE_KEYS_'][$key];
        }

        return $returnArray;
    }
}