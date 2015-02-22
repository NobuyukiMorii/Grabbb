<?php
// Controller/UsersController.php
class UsersController extends AppController {

    public $uses = array('User' , 'UserImage');

    public $components = array('RequestHandler','WebrootFileDir' ,'FileUpload');

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

            $message = array('result' => 'Success' , 'id' => $flg[0]['User']['id']);
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

        //idがあるかどうかを検索
        $user_exsitance = $this->User->find('first' , array(
            'conditions' =>array('id' => $this->request->data['id']),
            'fields'=>array('id')            
        ));
        //idがあった時
        if(!empty($user_exsitance)){
            // ログインステータスを更新
            $status = array('User' => array('id' => $this->request->data['id'], 'status' => 0));
            $fields = array('status');
            $flg = $this->User->save($status, false, $fields);

            if($flg){
                $message = array('result' => 'success');
            } else {
                $message = array('result' => 'error');
            }
        }
        //idがなかった時
        if(empty($user_exsitance)){
            $message = array('result' => 'error' , 'detail' => 'idExsitence');
        }
        //jsonを返す
        $this->set(array(
            'message' => $message,
            '_serialize' => array('message')
        ));
    }

    public function introductory_comment(){

        //idがあるかどうかを検索
        $user_exsitance = $this->User->find('first' , array(
            'conditions' =>array('id' => $this->request->data['id']),
            'fields'=>array('id')            
        ));
        //idがあった時
        if(!empty($user_exsitance)){
            // 自己紹介文を更新
            $introductory_comment = array('User' => array('id' => $this->request->data['id'], 'introductory_comment' => $this->request->data['introductory_comment']));
            $fields = array('introductory_comment');
            $this->User->id = $this->request->data['id'];  
            $flg = $this->User->save($introductory_comment, false, $fields);

            if($flg){
                $message = array('result' => 'success');
            } else {
                $message = array('result' => 'error');
            }       
        }
        //idがなかった時
        if(empty($user_exsitance)){
            $message = array('result' => 'error' , 'detail' => 'idExsitence');
        }
        //jsonを返す
        $this->set(array(
            'message' => $message,
            '_serialize' => array('message')
        ));

    }

    public function profile_image_upload(){
        //webrootディレクトリを取得
        $WebrootFileDir = $this->WebrootFileDir->GetWebrootFileDir();

        //idがあるかどうかの判定
        $user_exsitance = $this->User->find('first' , array(
            'conditions' =>array('id' => $this->request->data['id']),
            'fields'=>array('id')            
        ));

        //idがあった時
        if(!empty($user_exsitance)){
            //変数の設定
            $IMAGES_DIR = $WebrootFileDir.'/UserOriginalImages';
            $THUMBNAILS_DIR = $WebrootFileDir.'/UserThumbnailImages';
            $THUMBNAIL_WIDTH = 72;
            $MAX_FILE_SIZE = 307200;

            //アップロードエラー、ファイルサイズのエラーを検出
            $message = $this->FileUpload->UploadValidation($MAX_FILE_SIZE);
            //拡張子を取得する
            $imagesize = getimagesize($_FILES['image']['tmp_name']);
            //拡張子のエラーを検出
            $message = $this->FileUpload->UploadValidation($imagesize);
            //拡張子のタイプを検出
            $ext = $this->FileUpload->GetImageType($imagesize);
            //オリジナルの画像のファイル名を設定する
            $imageFileName = sha1(time().mt_rand()).$ext;
            //移動先のパスを指定する
            $imageFilePath = $IMAGES_DIR.'/'.$imageFileName;
            //ファイルをtmpからオリジナルイメージを入れるフォルダに移動
            $message = $this->FileUpload->UploadToOriginalImageFolder($imageFilePath);
            //画像をリサイズする
            $thumbImage = $this->FileUpload->MakeResizeImage($imageFilePath,$imageFileName,$imagesize,$THUMBNAIL_WIDTH);
            //リサイズした画像をアップロードする
            $this->FileUpload->UploadTHUMBNAILS_DIR($imagesize,$thumbImage , $THUMBNAILS_DIR , $imageFileName);
            // //UserImageテーブルにパスを保存する
            // $UserImageInfo = array('UserImage' => array(
            //     'user_id' => $this->request->data['id'],
            //     'path' => $imageFileName
            // ));
            // $flg = $this->UserImage->save($UserImageInfo);
            // //DBのsaveの判定
            // if($flg){
            //     $message = array('result' => 'success');
            // } else {
            //     $message = array('result' => 'error', 'detail' => 'DatabaseSaveErrror');
            // }
        } else {
            $message = array('result' => 'errror' , 'detail' => 'idExsitence');
        }
        //jsonを返す
        $message = 'hage';
        $this->set(array(
            'message' => $message,
            '_serialize' => array('message')
        ));
    }
}
