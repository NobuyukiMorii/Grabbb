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
        $this->request->data['password'] = sha1($this->request->data['password']);
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

    //ログイン処理
    public function login() {

        //検索してidを返す
        $flg = $this->User->find('all', array(
            'conditions'=>array(
                'and' => array(
                    'user_id' => $this->request->data['user_id'],
                    'password' => sha1($this->request->data['password'])
                )),
            'fields'=>array('id')
        ));
        //成功時の処理
        if($flg){
            // ログインステータスを更新
            $status = array('User' => array('id' => $flg[0]['User']['id'], 'status' => 1));
            $fields = array('status');
            $this->User->save($status, false, $fields);

            $message = array('result' => 'Success' , 'user_id' => $this->request->data['user_id']);
        }

        //失敗時の処理
        //passwordは存在するかの確認
        $flg_password = $this->User->find('all' , array(
            'conditions' => array('password' => sha1($this->request->data['password'])),
            'limit' => 1,
            'fields'=>array('password')
        ));
        if(empty($flg_password)) {
            $message = array('result' => 'Error' , 'detail' => 'password');
        }

        //user_idはあるかどうかの確認
        $flg_user_id = $this->User->find('all' , array(
            'conditions' => array('user_id' => $this->request->data['user_id']),
            'limit' => 1,
            'fields'=>array('user_id')
        ));
        if(empty($flg_user_id)){
            $message = array('result' => 'Error' , 'detail' => 'user_id');
        }

        $this->set(array(
            'message' => $message,
            '_serialize' => array('message')
        ));
    }

    public function logout(){
        // ログインステータスを更新
        $status = array('User' => array('id' => $this->request->data['id'], 'status' => 0));
        $fields = array('status');
        $flg = $this->User->save($status, false, $fields);

        if($flg){
            $message = array('result' => 'success');
        } else {
            $message = array('result' => 'error');
        }

        $this->set(array(
            'message' => $message,
            '_serialize' => array('message')
        ));
    }

}
