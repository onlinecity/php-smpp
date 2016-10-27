<?php
/**
 * Created by PhpStorm.
 * User: nfargere
 * Date: 27/10/2016
 * Time: 15:10
 */

namespace Phpsmpp\Protocol\Tags;


class Message_state_tag extends SmppTag
{
    public function __construct($id, $value, $length=null, $type='a*') {
        parent::__construct($id, $value, $length=null, $type='a*');
    }
}