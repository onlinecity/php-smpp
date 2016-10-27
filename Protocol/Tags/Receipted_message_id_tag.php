<?php
/**
 * Created by PhpStorm.
 * User: nfargere
 * Date: 27/10/2016
 * Time: 15:09
 */

namespace Phpsmpp\Protocol\Tags;


class Receipted_message_id_tag extends SmppTag
{
    public function __construct($id, $value, $length=null, $type='a*') {
        parent::__construct($id, $value, $length=null, $type='a*');
    }
}