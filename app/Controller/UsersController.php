<?php
// Controller/UsersController.php
class UsersController extends AppController {

    public $uses = array('User' , 'UserImage');

    public $components = array('RequestHandler','WebrootFileDir');

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
        $WebrootFileDir = $this->GetWebrootFileDir->WebrootFileDir();

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
            //アップロードエラーを検出する
            if($_FILES['image']['error'] != UPLOAD_ERR_OK){
                $message = array('result' => 'errror' , 'detail' => 'UploadError');
            }
            //サイズエラーを検出sるう
            $size = filesize($_FILES['image']['tmp_name']);
            if(!$size || $size > $MAX_FILE_SIZE){
                $message = array('result' => 'errror' , 'detail' => 'SizeErrror');
            }
            //画像の拡張子のエラーを検出する
            $imagesize = getimagesize($_FILES['image']['tmp_name']);
            switch ($imagesize['mime']) {
                case 'image/gif':
                    $ext = '.gif';
                    break;
                case 'image/jpeg':
                    $ext = '.jpg';
                    break;
                case 'image/png':
                    $ext = '.png';
                    break;
                default:
                    $message = array('result' => 'errror' , 'detail' => 'TypeErrror');
                    exit;
            }
            //オリジナルの画像のファイル名を設定する
            $imageFileName = sha1(time().mt_rand()).$ext;
            //元画像を保存
            $imageFilePath = $IMAGES_DIR.'/'.$imageFileName;

            $rs = move_uploaded_file($_FILES['image']['tmp_name'], $imageFilePath);

            if(!$rs){
                $message = array('result' => 'errror' , 'detail' => 'MoveUploadedFileErrror');
            }
            //画像の幅と高さを変数に代入
            $width = $imagesize[0];
            $height = $imagesize[1];
            //オリジナル画像が大きい時は、サムネイルを作成する

            //元ファイルを画像タイプを作る
            switch ($imagesize['mime']) {
            case 'image/gif':
                $srcImage = imagecreatefromgif($imageFilePath);
                break;
            case 'image/jpeg':
                $srcImage = imagecreatefromjpeg($imageFilePath);
                break;
            case 'image/png':
                $srcImage = imagecreatefrompng($imageFilePath);
                break;
            }
            //新しいサイズを作る
            $thumbHeight = round($height * $THUMBNAIL_WIDTH / $width);
            //縮小画像を生成
            $thumbImage = imagecreatetruecolor($THUMBNAIL_WIDTH, $thumbHeight);
            imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, 72, $thumbHeight, $width, $height);
            
            //縮小画像を保存する
            switch ($imagesize['mime']) {
            case 'image/gif':
                imagegif($thumbImage, $THUMBNAILS_DIR.'/'.$imageFileName);
                break;
            case 'image/jpeg':
                imagejpeg($thumbImage, $THUMBNAILS_DIR.'/'.$imageFileName);
                break;
            case 'image/png':
                imagepng($thumbImage, $THUMBNAILS_DIR.'/'.$imageFileName);
                break;
            }
            //UserImageテーブルにパスを保存する
            $UserImageInfo = array('UserImage' => array(
                'user_id' => $this->request->data['id'],
                'path' => $imageFileName
            ));
            $flg = $this->UserImage->save($UserImageInfo);
            //DBのsaveの判定
            if($flg){
                $message = array('result' => 'success');
            } else {
                $message = array('result' => 'error', 'detail' => 'DatabaseSaveErrror');
            }
        } else {
            $message = array('result' => 'errror' , 'detail' => 'idExsitence');
        }
        //jsonを返す
        $this->set(array(
            'message' => $message,
            '_serialize' => array('message')
        ));
    }
}
