<?php

namespace BisonLab\CommonBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;

/*
 * Does as little as possible.
 */

class ChangeTracker 
{
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        if ($eventArgs->hasChangedField('attributes_json')) {
            if ($json = $eventArgs->getNewValue('attributes_json')) {
                $la = $this->array_change_key_case_recursive(json_decode($json, true), MB_CASE_LOWER);
                $json = json_encode($la);
                $eventArgs->setNewValue('attributes_json', $json);
            }
        }
    }

    public function array_change_key_case_recursive($arr, $case = MB_CASE_LOWER)
    {
        $ret = array();
        foreach ($arr as $k => $v) {
            if(is_array($v))
                $v = $this->array_change_key_case_recursive($v, $case);
            $ret[mb_convert_case($k, $case, "UTF-8")] = $v;
        }
        return $ret;
    }
}
