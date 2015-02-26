<?php

class UserMessage extends AppModel {

    public $belongsTo = array(
        'UserRoom' => array(
            'className'    => 'UserRoom',
            'foreignKey'   => 'user_room_id'
        )
    );

}