<?php

class User extends AppModel {

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
	    )
    );

}