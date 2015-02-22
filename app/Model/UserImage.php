<?php

class UserImage extends AppModel {

    public $belongsTo = array(
        'User' => array(
            'className'    => 'User',
            'foreignKey'   => 'user_id'
        )
    );

}