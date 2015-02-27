<?php

class UserRoom extends AppModel {

    public $belongsTo = array(
        'User' => array(
            'className'    => 'User',
            'foreignKey'   => 'user_id'
        )
    );

    public $hasMany = array(
        'UserMessage' => array(
            'className'  => 'UserMessage',
            'foreignKey'    => 'user_room_id',
        )
    );

}