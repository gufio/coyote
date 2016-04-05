<?php

namespace Coyote\Parser\Reference;

class City
{
    /**
     * Transform string (with cities separated by comma) to array
     *
     * @param $string
     * @return array
     */
    public function grab($string)
    {
        return array_filter(array_unique(array_map('trim', preg_split('/[\/,]/', $string))));
    }
}
