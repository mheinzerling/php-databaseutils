<?php

namespace mheinzerling\commons;


class ArrayUtils
{
    public static function mergeArrayKeys(array $a1, array $a2):array
    {
        $a1Keys = array_keys($a1);
        $a2Keys = array_keys($a2);
        $uniqueSortedKeys = array_unique(array_merge($a1Keys, $a2Keys));
        sort($uniqueSortedKeys);
        return $uniqueSortedKeys;
    }

    //TODO move
    /**
     * @param string $other
     * @param string $regex
     * @return null|string
     */
    public static function findAndRemove(string &$other, string $regex)
    {
        if (preg_match($regex, $other, $match)) {
            $other = str_replace($match[0], "", $other);
            return $match[1];
        }
        return null;
    }
} 