<?php

class User extends AppModel {

    public $hasMany = array(
        'UserImage' => array(
            'className'  => 'UserImage',
            'foreignKey'    => 'user_id',
        ),
        'UserLocation' => array(
            'className'  => 'UserLocation',
            'foreignKey'    => 'user_id',
            'order'      => 'created DESC'
        ),
        'UserMessage' => array(
            'className'  => 'UserMessage',
            'foreignKey'    => 'user_id',
        ),
    );

    public $validate = array(
        'user_id' => array(
        	'isUnique' => array(
				'rule'     => 'isUnique',
                'required' => 'create',
                'message'  => 'isUnique',
                'last'    => false
        	),
            'alphaNumeric' => array(
                'rule'     => 'alphaNumeric',
                'required' => true,
                'message'  => 'alphaNumeric',
                'last'    => false
            ),
            'between' => array(
                'rule'    => array('between', 5, 15),
                'message' => 'between'
            ),
 			'notEmpty' => array(
                'rule'    => 'notEmpty',
                'message' => 'notEmpty',
                'last'    => false
            )
        ),
        'nickname' => array(
            'between' => array(
                'rule'    => array('between', 5, 15),
                'message' => 'between'
            ),
 			'notEmpty' => array(
                'rule'    => 'notEmpty',
                'message' => 'notEmpty',
                'last'    => false
            )
        ),
        'password' => array(
            'alphaNumeric' => array(
                'rule'     => 'alphaNumeric',
                'required' => true,
                'message'  => 'alphaNumeric',
                'last'    => false
            ),
 			'notEmpty' => array(
                'rule'    => 'notEmpty',
                'message' => 'notEmpty',
                'last'    => false
            )
        ),
	    'email' => array(
	    	'email' => array(
		        'rule'    => array('email', true),
		        'message' => 'email',
		        'last'    => false
		    ),
        	'isUnique' => array(
				'rule'     => 'isUnique',
                'message'  => 'isUnique',
                'last'    => false
        	),
 			'notEmpty' => array(
                'rule'    => 'notEmpty',
                'message' => 'notEmpty',
                'last'    => false
            )
	    ),
        'introductory_comment' => array(
            'between' => array(
                'rule'    => array('between', 5, 15),
                'message' => 'between'
            ),
        ),
        'status' => array(
             'allowedChoice' => array(
                 'rule' => array('inList', array(0, 1)),
                 'message' => 'allowedChoice'
             )
        )
    );

}