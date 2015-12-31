<?php
class PrefixRootURLController extends RootURLController
{
    
    /**
     * @param boolean $isAtRoot 
     */
    public static function set_is_at_root($isAtRoot = true)
    {
        parent::$is_at_root = ($isAtRoot) ? true: false;
    }
}
