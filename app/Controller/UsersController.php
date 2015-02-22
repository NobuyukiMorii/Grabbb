<?php
// Controller/UsersController.php
class UsersController extends AppController {

    public $components = array('RequestHandler');

    public function index() {
        $users = $this->User->find('all');
        $this->set(array(
            'users' => $users,
            '_serialize' => array('user')
        ));
    }

    public function view($id) {
        $user = $this->User->findById($id);
        $this->set(array(
            'user' => $user,
            '_serialize' => array('user')
        ));
    }

    public function add() {
        $this->User->create();
        $result = $this->User->save($this->request->data);
        if ($result) {
            $message = array('result' => 'Saved', 'id' => $this->User->getLastInsertID());
        } else {
            $message = array('result' => 'Error' , 'detail' => $this->User->validationErrors);
        }
        $this->set(array(
            'message' => $message,
            '_serialize' => array('message')
        ));
    }

    public function edit($id) {
        $this->User->id = $id;
        if ($this->User->save($this->request->data)) {
            $message = 'Saved';
        } else {
            $message = 'Error';
        }
        $this->set(array(
            'message' => $message,
            '_serialize' => array('message')
        ));
    }

    public function delete($id) {
        if ($this->User->delete($id)) {
            $message = 'Deleted';
        } else {
            $message = 'Error';
        }
        $this->set(array(
            'message' => $message,
            '_serialize' => array('message')
        ));
    }

}
