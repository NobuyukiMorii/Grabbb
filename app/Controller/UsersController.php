<?php
// Controller/UsersController.php
class UsersController extends AppController {

    public $uses = array('User' , 'UserImage' , 'UserLocation' , 'UserMessage' , 'UserRoom');

    public $components = array('RequestHandler','WebrootFileDir' ,'FileUpload' , 'Gurunabi');

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

    public function edit() {
        if(isset($this->request->data['id'])){
            $result['id'] = $this->request->data['id'];
        } else {
            $message = array('result' => 'error' , 'detail' => 'RequestErrpr');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        if(!empty($this->request->data['user_id'])){
            $this->User->id = $this->request->data['id'];
            $result['user_id'] = $this->User->saveField('user_id', $this->request->data['user_id']);
            if(empty($result['user_id'])){
                $error_flg = 'user_id';
            } else {
                $result['user_id'] = $result['user_id']['User']['user_id'];
            }
        }
        if(!empty($this->request->data['nickname'])){
            $this->User->id = $this->request->data['id'];
            $result['nickname'] = $this->User->saveField('nickname', $this->request->data['nickname']);
            if(empty($result['nickname'])){
                $error_flg = 'nickname';
            } else {
                $result['nickname'] = $result['nickname']['User']['nickname'];
            }
        }
        if(!empty($this->request->data['email'])){
            $this->User->id = $this->request->data['id'];
            $result['email'] = $this->User->saveField('email', $this->request->data['email']);
            if(empty($result['email'])){
                $error_flg = 'email';
            } else {
                $result['email'] = $result['email']['User']['email'];
            }
        }
        if(!empty($this->request->data['introductory_comment'])){
            $this->User->id = $this->request->data['id'];
            $result['introductory_comment'] = $this->User->saveField('introductory_comment', $this->request->data['introductory_comment']);
            if(empty($result['introductory_comment'])){
                $error_flg = 'introductory_comment';
            } else {
                $result['introductory_comment'] = $result['email']['User']['introductory_comment'];
            }
        }
        if(!empty($this->request->data['password'])){
            $this->request->data['password'] = sha1($this->request->data['password']);
            $this->User->id = $this->request->data['id'];
            $result['password'] = $this->User->saveField('password', $this->request->data['password']);
            if(empty($result['password'])){
                $error_flg = 'password';
            } else {
                $result['password'] = $result['password']['User']['password'];
            }
        }
        $result = $this->User->find('first' ,array(
            'conditions' => array('id' => $this->request->data['id'])
        ));
        if (!isset($error_flg)) {
            $message = array('result' => 'Saved', 'detail' => $result);
        } else {
            $message = array('result' => 'Error' , 'detail' => $error_flg);
        }
        $this->set(array('message' => $message, '_serialize' => array('message')));
    }

    public function delete() {
        /*
        *リクエストがない場合のエラー
        */
        if(empty($this->request->data)){
            $message = array('result' => 'error' , 'detail' => 'RequestErrpr');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *物理削除
        */
        $delete['id'] = $this->User->delete($this->request->data['id']);
        $delete['image'] = $this->UserImage->deleteAll(array('UserImage.user_id' => $this->request->data['id']), false);
        $delete['location'] = $this->UserLocation->deleteAll(array('UserLocation.user_id' => $this->request->data['id']), false);
        /*
        *論理削除
        */
        $this->UserMessage->unbindModel(array(
            'belongsTo' => array('User' , 'UserRoom')
            )
        );
        $user_messages = $this->UserMessage->find('all' , array(
            'conditions' => array('UserMessage.user_id' => $this->request->data['id'])
        ));
        foreach ($user_messages as $key => $value) {
            $this->UserMessage->id = $value['UserMessage']['id'];
            $delete['message'] = $this->UserMessage->saveField('user_id', 1);
        }

        $this->UserRoom->unbindModel(array(
            'belongsTo' => array('User'),
            'hasMany' => array('UserMessage')
            )
        );
        $user_rooms = $this->UserRoom->find('all' , array(
            'conditions' => array('UserRoom.user_id' => $this->request->data['id'])
        ));
        foreach ($user_rooms as $key => $value) {
            $this->UserRoom->id = $value['UserRoom']['id'];
            $delete['message'] = $this->UserRoom->saveField('user_id', 1);
        }

        $message = array('result' => 'Deleted', 'detail' => $delete);
        $this->set(array('message' => $message, '_serialize' => array('message')));

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

    public function profile_image_upload($id){

        if($_FILES['image']['size'] !== 0){
            //webrootディレクトリを取得
            $WebrootFileDir = $this->WebrootFileDir->GetWebrootFileDir();

            //idがあるかどうかの判定
            $user_exsitance = $this->User->find('first' , array(
                'conditions' =>array('id' => $id),
                'fields'=>array('id')            
            ));

            //idがあった時
            if(!empty($user_exsitance)){

                //変数の設定
                $IMAGES_DIR = $WebrootFileDir.'/UserOriginalImages';
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
                //UserImageテーブルにパスを保存する
                $UserImageInfo = array('UserImage' => array(
                    'user_id' => $id,
                    'path' => $imageFileName
                ));
                $flg = $this->UserImage->save($UserImageInfo);
                //DBのsaveの判定
                if($flg){
                    $message = array('result' => 'success' , 'FilePath' => 'http://mory.weblike.jp/Grabbb/app/webroot/UserOriginalImages/' .$imageFileName);
                } else {
                    $message = array('result' => 'error', 'detail' => 'DatabaseSaveErrror');
                }
            } else {
                $message = array('result' => 'errror' , 'detail' => 'idExsitence');
            }
        } else {
            $message = array('result' => 'errror' , 'detail' => 'FileSize' . $_FILES['image']['size'] );
        }

        //jsonを返す
        $this->set(array(
            'message' => $message,
            '_serialize' => array('message')
        ));
    }

    public function trace_user(){
        /*
        *リクエストがない場合のエラー
        */
        if(empty($this->request->data)){
            $message = array('result' => 'error' , 'detail' => 'RequestErrpr');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *変数を設定
        */
        $location_data['id'] = null;
        $location_data['user_id'] = $this->request->data['id'];
        $location_data['latitude'] = $this->request->data['latitude'];
        $location_data['longitude'] = $this->request->data['longitude'];

        /*
        *ログインユーザーで近い人を検索
        */
        //①ログイン状態のユーザーを抽出する
        $login_users = $this->User->find('all', array(
            'conditions' => array('status' => 1)
        ));
        //②ログインユーザーの緯度と経度から直線距離を算出する（新しい順）
        $i = 0;
        foreach ($login_users as $key => $value) {
            if(isset($value['UserLocation'][0]['latitude'])){
            if(isset($value['UserLocation'][0]['longitude'])){
                $latitude_diff = abs($location_data['latitude'] - $value['UserLocation'][0]['latitude']);
                $longitude_diff = abs($location_data['longitude'] - $value['UserLocation'][0]['longitude']);
                $distance = sqrt($latitude_diff * $latitude_diff + $longitude_diff * $longitude_diff);
                $value['distance'] = $distance;
                $distance_key[$key] = $value['distance'];
                $near_users[$key] = $value;
            }} 
        }
        //③並び替える
        array_multisort ($distance_key , SORT_ASC , $near_users);

        /*
        *エラー処理
        */
        //idがない場合はエグジット
        $user_id_exsitance = $this->User->find('first' , array(
            'conditions' =>array('id' => $this->request->data['id']),
            'fields'=>array('id')            
        ));
        if(empty($user_id_exsitance)) {
            $message = array('result' => 'errror' , 'detail' => 'UserIdExsitence');
            $this->set(array('message' => $message, '_serialize' => array('message')));
            return;
        }
        /*
        *緯度と経度を取得して、保存
        */

        //user_locationテーブルのuser_idの数をカウント
        $location_log = $this->UserLocation->find('first' , array(
            'conditions' => array('user_id' => $location_data['user_id'])
        ));
        /*
        *user_locationにuser_idが存在しない時
        */
        if(!$location_log){
            $this->UserLocation->create();
            $flg = $this->UserLocation->save($location_data);  
            if($flg){
                $message = array('result' => 'success' , 'near_users' => $near_users , 'location_log_id' => $this->UserLocation->getLastInsertID(), 'latitude' => $location_data['latitude'], 'longitude' => $location_data['longitude']);
            } else {
                $message = array('result' => 'error' , 'detail' => 'CreateError');
            }
           
        }
        /*
        *user_locationが存在する時
        */
        if($location_log){
            $this->UserLocation->id = $location_log['UserLocation']['id'];
            $location_data['id'] = $location_log['UserLocation']['id'];

            $flg = $this->UserLocation->save($location_data);  
            if($flg){
                $message = array('result' => 'success' , 'near_users' => $near_users , 'location_log_id' => $location_log['UserLocation']['id'] , 'latitude' => $location_data['latitude'], 'longitude' => $location_data['longitude']);
            } else {
                $message = array('result' => 'error' , 'detail' => 'UpdateError');
            }
        }
        
        $this->set(array('message' => $message, '_serialize' => array('message')));
    }

    public function suggest_cafe(){
        /*
        *リクエストがない場合のエラー
        */
        if(empty($this->request->data)){
            $message = array('result' => 'error' , 'detail' => 'RequestErrpr');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *変数の設定
        */
        $id = $this->request->data['id'];
        $partner_id = $this->request->data['partner_id'];
        /*
        *$idユーザーの位置情報を取得
        */
        $id_location = $this->UserLocation->find('first' ,array(
            'conditions' => array('user_id' => $id),
            'order' => array('UserLocation.created DESC'), 
        ));
        if(empty($id_location)){
            $message = array('result' => 'error' , 'detail' => 'NoIdLocation');
            $this->set(array('message' => $message, '_serialize' => array('message')));
            return;
        }
        /*
        *$partner_idのユーザーの位置情報を取得
        */
        $partner_location = $this->UserLocation->find('first' ,array(
            'conditions' => array('user_id' => $partner_id),
            'order' => array('UserLocation.created DESC'), 
        ));
        if(empty($partner_location)){
            $message = array('result' => 'error' , 'detail' => 'NoPartner_IdLocation');
            $this->set(array('message' => $message, '_serialize' => array('message')));
            return;
        }
        /*
        *中間地点を計算
        */
        $middle_location['latitude'] = ($id_location['UserLocation']['latitude'] + $partner_location['UserLocation']['latitude']) * 0.5;
        $middle_location['longitude'] = ($id_location['UserLocation']['longitude'] + $partner_location['UserLocation']['longitude']) * 0.5;
        /*
        *ぐるなびにアクセス
        */
        $gurunabi_url = $this->Gurunabi->make_gnaviapi_url($middle_location['latitude'] , $middle_location['longitude']);
        //xml形式のデータを連想配列のデータに変更する
        $cafes = $this->Gurunabi->parse_xml_to_array($gurunabi_url);
        //連想配列から特定の値を取り出す
        $cafes = $this->Gurunabi->get_rest_info($cafes);
        $message = array('result' => 'success' , 'cafe' => $cafes);
        $this->set(array('message' => $message, '_serialize' => array('message')));

    }


    //緯度と経度をゆーきから送ってもらえる
    public function suggest_cafe_around_user(){
        /*
        *リクエストがない場合のエラー
        */
        if(empty($this->request->data)){
            $message = array('result' => 'error' , 'detail' => 'RequestErrpr');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *ぐるなびにアクセス
        */
        $gurunabi_url = $this->Gurunabi->make_gnaviapi_url($this->request->data['latitude'] , $this->request->data['longitude']);
        //xml形式のデータを連想配列のデータに変更する
        $cafes = $this->Gurunabi->parse_xml_to_array($gurunabi_url);
        //連想配列から特定の値を取り出す
        $cafes = $this->Gurunabi->get_rest_info($cafes);
        /*
        *位置情報を保存する
        */
        /*
        *変数を設定
        */
        $location_data['id'] = null;
        $location_data['user_id'] = $this->request->data['id'];
        $location_data['latitude'] = $this->request->data['latitude'];
        $location_data['longitude'] = $this->request->data['longitude'];
        //user_locationテーブルのuser_idの数をカウント
        $location_log = $this->UserLocation->find('first' , array(
            'conditions' => array('user_id' => $this->request->data['id'])
        ));
        /*
        *user_locationにuser_idが存在しない時
        */
        if(!$location_log){
            $this->UserLocation->create();
            $flg = $this->UserLocation->save($location_data);  
            if($flg){
                $message = array('result' => 'success' , 'latitude' => $location_data['latitude'], 'longitude' => $location_data['longitude'] , 'cafes' => $cafes);
            } else {
                $message = array('result' => 'error' , 'detail' => 'CreateError');
            }
           
        }
        /*
        *user_locationが存在する時
        */
        if($location_log){
            $this->UserLocation->id = $location_log['UserLocation']['id'];
            $location_data['id'] = $location_log['UserLocation']['id'];

            $flg = $this->UserLocation->save($location_data);  
            if($flg){
                $message = array('result' => 'success' , 'latitude' => $location_data['latitude'], 'longitude' => $location_data['longitude'] , 'cafes' => $cafes);
            } else {
                $message = array('result' => 'error' , 'detail' => 'UpdateError');
            }
        }
        $this->set(array('message' => $message, '_serialize' => array('message')));

    }

    public function get_transfer_time(){
        /*
        *リクエストがない場合のエラー
        */
        if(empty($this->request->data)){
            $message = array('result' => 'error' , 'detail' => 'RequestErrpr');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *変数の設定
        */
        $id = $this->request->data['id'];
        $partner_id = $this->request->data['partner_id'];
        /*
        *$idユーザーの位置情報を取得
        */
        $id_location = $this->UserLocation->find('first' ,array(
            'conditions' => array('user_id' => $id),
            'order' => array('UserLocation.created DESC'), 
        ));
        if(empty($id_location)){
            $message = array('result' => 'error' , 'detail' => 'NoIdLocation');
            $this->set(array('message' => $message, '_serialize' => array('message')));
            return;
        }
        /*
        *$partner_idのユーザーの位置情報を取得
        */
        $partner_location = $this->UserLocation->find('first' ,array(
            'conditions' => array('user_id' => $partner_id),
            'order' => array('UserLocation.created DESC'), 
        ));
        if(empty($partner_location)){
            $message = array('result' => 'error' , 'detail' => 'NoPartner_IdLocation');
            $this->set(array('message' => $message, '_serialize' => array('message')));
            return;
        }
        /*
        *お店の緯度と経度を取得
        */
        $cafe_location['latitude'] = $this->request->data['shop_latitude'];
        $cafe_location['longitude'] = $this->request->data['shop_longitude'];

        /*
        *サンプルデータの代入
        */
        //東京周辺
        $cafe_location['latitude'] = 35.65858;
        $cafe_location['longitude'] = 139.745433;
        //神田周辺
        $id_location['UserLocation']['latitude'] = 35.69169;
        $id_location['UserLocation']['longitude'] = 139.770883;
        //新橋周辺
        $partner_location['UserLocation']['latitude'] = 35.665498;
        $partner_location['UserLocation']['longitude'] = 139.75964;

        //$idユーザーとお店との距離
        $GoogleMapsApiUrl ='http://maps.googleapis.com/maps/api/distancematrix/xml?origins='.$id_location['UserLocation']['latitude'].','.$id_location['UserLocation']['longitude'].'&destinations='.$cafe_location['latitude'].','.$cafe_location['longitude'].'&mode=walk&language=ja&sensor=false';
        $transfer_cost['id'] = simplexml_load_file($GoogleMapsApiUrl);

        //$user_idユーザーとお店との距離
        $GoogleMapsApiUrl ='http://maps.googleapis.com/maps/api/distancematrix/xml?origins='.$partner_location['UserLocation']['latitude'].','.$partner_location['UserLocation']['longitude'].'&destinations='.$cafe_location['latitude'].','.$cafe_location['longitude'].'&mode=walk&language=ja&sensor=false';
        $transfer_cost['partner'] = simplexml_load_file($GoogleMapsApiUrl); 

        /*
        *メッセージを返す
        */       
        $message = array('result' => 'success' , 'id' => $transfer_cost);
        $this->set(array('message' => $message, '_serialize' => array('message')));

    }


    public function find_users_around_cafe(){
        /*
        *リクエストがない場合のエラー
        */
        if(empty($this->request->data)){
            $message = array('result' => 'error' , 'detail' => 'RequestErrpr');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *変数を定義
        */
        //id
        $id = $this->request->data['id'];
        //緯度
        $latitude = $this->request->data['latitude'];
        //経度
        $longitude = $this->request->data['longitude'];
        //距離
        $distance = $this->request->data['distance'];
        /*
        *緯度と経度の範囲を計算
        *http://blog.epitaph-t.com/?p=172
        */
        $plus_latitude = $latitude + ($distance / 30.8184 * 0.000277778);
        $plus_longitude = $longitude + ($distance / 25.2450 * 0.000277778);
        $minus_latitude = $latitude - ($distance / 30.8184 * 0.000277778);
        $minus_longitude = $longitude  - ($distance / 25.2450 * 0.000277778);
        /*
        *データベースから範囲内のユーザーを検索する
        */
        $users = $this->UserLocation->find('all' , array(
            'conditions' => array(
                'AND' =>
                    array(
                        array('latitude >=' =>  $minus_latitude),
                        array('latitude <=' =>  $plus_latitude),
                        array('longitude >=' =>  $minus_longitude),
                        array('longitude <=' =>  $plus_longitude),
                        array('NOT' => array('UserLocation.user_id' => $id))
                    ),
            ),
            'order' => array('created DESC')
        ));
        /*
        *ユーザーの情報を全件検索する
        */
        foreach ($users as $key => $value) {
            $this->User->unbindModel(array(
                'hasMany' => array('UserImage' , 'UserLocation' , 'UserMessage'),
                )
            );
            $users_info[$key] = $this->User->find('all' , array(
                'conditions' => array('id' => $value['UserLocation']['user_id'])
            ));
        }
        /*
        *ユーザーが範囲内にいなかった時
        */
        if(empty($users)){
            $message = array('result' => 'error','detail' => 'NoUsersHere');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *メッセージを返す
        */       
        $message = array('result' => 'success' , 'users' => $users , 'user_info' => $users_info);
        $this->set(array('message' => $message, '_serialize' => array('message')));

    }

    public function return_room_id(){
        //自分のidじゃない方を返せ

        /*
        *リクエストがない場合のエラー
        */
        if(empty($this->request->data)){
            $message = array('result' => 'error' , 'detail' => 'RequestErrpr');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *id名からグループ名を検索
        */
        $this->UserRoom->unbindModel(array(
            'hasMany' => array('UserMessage'),
            )
        );
        $rooms = $this->UserRoom->find('all' , array(
            'conditions' => array(
                'OR' =>
                    array(
                        array('UserRoom.partner_id' => $this->request->data['id']),
                        array('UserRoom.user_id' => $this->request->data['id']),
                    ),
            ),
            'order' => array('UserRoom.created DESC')
        ));
        /*
        *ルームがない時
        */
        if(empty($rooms)){
            $message = array('result' => 'success' , 'rooms' => array() ,'detail' => 'UserHasNoRoom');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        foreach ($rooms as $key => $value) {
            /*
            *パートナーidがUseのidと一緒かどうかを判定
            */
            //ユーザーがとれていない時のエラーを消す
            if(empty($value['User'])){
                $value['User']['id'] = null;
            }
            if($value['User']['id'] !== $this->request->data['id']){
                $rooms[$key]['Partner'] = $value['User'];
            } 
            if($value['User']['id'] == $this->request->data['id']){
                $this->User->unbindModel(array(
                    'hasMany' => array('UserImage' , 'UserLocation' , 'UserMessage'),
                    )
                );
                $Partner = $this->User->find('first' ,array(
                    'conditions' => array('id' => $value['UserRoom']['partner_id'] )
                ));
                //Partnerのidが存在しなかった時
                if(empty($Partner['User'])){
                    $Partner['User']['id'] = null;
                    $Partner['User']['user_id'] = null;
                    $Partner['User']['partner_id'] = null;
                    $Partner['User']['created'] = null;
                    $Partner['User']['modified'] = null;
                    $Partner['User']['errror'] = "NoUserExistence";
                }
                $rooms[$key]['Partner'] = $Partner['User'];
            }
            unset($rooms[$key]['User']);
        }
        /*
        *メッセージを返す
        */       
        $message = array('result' => 'success' , 'rooms' => $rooms);
        $this->set(array('message' => $message, '_serialize' => array('message')));

    }

    public function ControlConversation(){
        /*
        *リクエストがない場合のエラー
        */
        if(empty($this->request->data)){
            $message = array('result' => 'error' , 'detail' => 'RequestErrpr');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *変数を設定する
        */
        $data['user_id'] = $this->request->data['id'];
        $data['partner_id'] = $this->request->data['partner_id'];
        /*
        *idがない場合はエグジット
        */
        $user_id_exsitance = $this->User->find('all' , array(
            'conditions' => array(
                'AND' => array(
                    'id' => array($data['user_id'],$data['partner_id'])
                )
            ),           
        ));
        if(empty($user_id_exsitance)) {
            $message = array('result' => 'errror' , 'detail' => 'UserIdExsitence');
            $this->set(array('message' => $message, '_serialize' => array('message')));
            return;
        }
        /*
        *過去の投稿を検索する
        */
        $PastChatForUser_id = $this->UserMessage->find('all' , array(
            'conditions' => array(
                'UserMessage.user_id' => $data['user_id'],
                'UserMessage.partner_id' => $data['partner_id']
            )
        ));
        $PastChatForPartner_id = $this->UserMessage->find('all' , array(
            'conditions' => array(
                'UserMessage.partner_id' => $data['user_id'],
                'UserMessage.user_id' => $data['partner_id']
            )
        ));
        /*
        *過去の投稿が１件もない場合にはChatRoomを作成する
        */
        if(empty($PastChatForUser_id) && empty($PastChatForPartner_id)){
            $this->UserRoom->create();
            $group_data = $this->UserRoom->save($data);
            /*
            *saveに失敗したらエラーメッセージ
            */
            if(empty($group_data)){
                $message = array('result' => 'error' , 'detail' => 'UserRoomSaveError');
                $this->set(array('message' => $message, '_serialize' => array('message')));   
                return;
            }
            /*
            *メッセージを保存
            */
            $data['message'] = $this->request->data['message'];
            $data['user_room_id'] = $this->UserRoom->getLastInsertID();
            $this->UserMessage->create();
            $message_data = $this->UserMessage->save($data);
            /*
            *saveに失敗したらエラーメッセージ
            */
            if(empty($message_data)){
                $message = array('result' => 'error' , 'detail' => 'UserRoomSaveError');
                $this->set(array('message' => $message, '_serialize' => array('message')));   
                return;
            }

        }
        if(!empty($PastChatForUser_id) || !empty($PastChatForPartner_id)){
            /*
            *id側の投稿があれば、そのルームidを設定する
            */
            if(!empty($PastChatForUser_id)){
                $data['user_room_id'] = $PastChatForUser_id[0]['UserMessage']['user_room_id'];
            }
            /*
            *partner側の投稿があれば、そのルームidを設定する
            */
            if(!empty($PastChatForPartner_id)){
                $data['user_room_id'] = $PastChatForPartner_id[0]['UserMessage']['user_room_id'];
            }
            /*
            *メッセージの保存
            */
            $data['message'] = $this->request->data['message'];
            $this->UserMessage->create();
            $message_data = $this->UserMessage->save($data);
            /*
            *saveに失敗したらエラーメッセージ
            */
            if(empty($message_data)){
                $message = array('result' => 'error' , 'detail' => 'UserRoomSaveError');
                $this->set(array('message' => $message, '_serialize' => array('message')));   
                return;
            }

        }
        /*
        *グループidから今までのチャットを全件検索する
        */
        $all_chat_data = $this->UserMessage->find('all' ,array(
            'conditions' => array('user_room_id' => $data['user_room_id'])
        ));
        /*
        *サクセスのjsonを返す
        */
        pr($all_chat_data);
        pr($data['user_room_id']);
        exit;
        $message = array('result' => 'success' , 'user_room_id' => $data['user_room_id'] , 'chat_data' => $all_chat_data);
        $this->set(array('message' => $message, '_serialize' => array('message')));
        return;
    }


    public function return_messages(){
        /*
        *リクエストがない場合のエラー
        */
        if(empty($this->request->data)){
            $message = array('result' => 'error' , 'detail' => 'RequestErrpr');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *メッセージを検索する
        */
        $messages = $this->UserMessage->find('all' , array(
            'conditions' => array(
                'user_room_id' => $this->request->data['user_room_id']
            )
        ));
        /*
        *グループidを検索する
        */
        $this->UserRoom->unbindModel(
            array(
                'belongsTo' => array('User'),
                'hasMany' => array('UserMessage')
            )
        );
        $room = $this->UserRoom->find('first' , array(
            'conditions' => array('UserRoom.id' => $this->request->data['user_room_id'])
        ));
        if(empty($room)){
            $message = array('result' => 'error' , 'detail' => 'NoRoom');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *パートナーidを指定する
        */
        if($room['UserRoom']['user_id'] == $this->request->data['id']){
            $partner_id = $room['UserRoom']['partner_id'];
        }
        if($room['UserRoom']['partner_id'] == $this->request->data['id']){
            $partner_id = $room['UserRoom']['user_id'];
        }
        if(empty($partner_id)){
            $message = array('result' => 'error' , 'detail' => 'NoPartnerIdInThisRoom');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *自分のUser情報を検索する
        */
        $this->User->unbindModel(array(
            'hasMany' => array('UserImage' , 'UserLocation' , 'UserMessage'),
            )
        );
        $myself = $this->User->find('first' , array(
            'conditions' => array('id' => $this->request->data['id'])
        ));
        if(empty($myself)){
            $message = array('result' => 'error' , 'detail' => 'NoMyself');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *パートナーのニックネームを返信する
        */
        $this->User->unbindModel(array(
            'hasMany' => array('UserImage' , 'UserLocation' , 'UserMessage'),
            )
        );
        $partner_user = $this->User->find('first' , array(
            'conditions' => array('id' => $partner_id)
        ));
        if(empty($partner_user)){
            $message = array('result' => 'error' , 'detail' => 'NoPartnerUser');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *メッセージを返す
        */       
        $message = array('result' => 'success' , 'messages' => $messages, 'myself' => $myself ,'partner_user' => $partner_user);
        //$message = array('result' => 'success' , 'user_room_id' => $data['user_room_id'] , 'chat_data' => $messages);
        $this->set(array('message' => $message, '_serialize' => array('message')));

    }

    public function new_chat_conversation(){
        /*
        *リクエストがない場合のエラー
        */
        if(empty($this->request->data)){
            $message = array('result' => 'error' , 'detail' => 'RequestError');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *ユーザーが3回以上通報されているかどうかを調べる
        */
        if(empty($this->request->data['partner_id']) && empty($this->request->data['message']) && empty($this->request->data['room_id'])) {
            $this->User->unbindModel(array(
                'hasMany' => array('UserImage' , 'UserLocation' , 'UserMessage'),
                )
            );
            $user_check = $this->User->find('first' , array(
                'conditions' => array('id' => $this->request->data['id'])
            ));
            if($user_check['User']['report_count'] > 2){
                $message = array('result' => 'error' , 'detail' => 'ReportedError');
                $this->set(array('message' => $message, '_serialize' => array('message')));   
                return;
            } else {
                $message = array('result' => 'success' , 'detail' => 'User is not in black list');
                $this->set(array('message' => $message, '_serialize' => array('message')));   
                return;
            }
        }
        /*
        *room_idがある場合
        */
        if(empty($this->request->data['room_id'])){
            /*
            *ユーザーが3回以上通報されているかどうかを調べる
            */
            $this->User->unbindModel(array(
                'hasMany' => array('UserImage' , 'UserLocation' , 'UserMessage'),
                )
            );
            $user_check = $this->User->find('first' , array(
                'conditions' => array('id' => $this->request->data['id'])
            ));
            if($user_check['User']['report_count'] > 2){
                $message = array('result' => 'error' , 'detail' => 'ReportedError');
                $this->set(array('message' => $message, '_serialize' => array('message')));   
                return;
            }
            /*
            *idがない場合はエグジット
            */
            $this->User->unbindModel(array(
                'hasMany' => array('UserImage' , 'UserLocation' , 'UserMessage'),
                )
            );
            $user_id_exsitance = $this->User->find('all' , array(
                'conditions' => array(
                    'AND' => array(
                        'id' => array($this->request->data['id'],$this->request->data['partner_id'])
                    )
                ),           
            ));
            if(empty($user_id_exsitance)) {
                $message = array('result' => 'errror' , 'detail' => 'UserIdExsitence');
                $this->set(array('message' => $message, '_serialize' => array('message')));
                return;
            }
            /*
            *グループを検索
            */
            $conditions = array(
                'OR' =>
                    array(
                        array( 'and' => array('UserRoom.user_id'=>$this->request->data['id'], 'UserRoom.partner_id'=>$this->request->data['partner_id'])),
                        array( 'and' => array('UserRoom.user_id'=>$this->request->data['partner_id'], 'UserRoom.partner_id'=>$this->request->data['id']))
                    )
            );
            $this->UserRoom->unbindModel(array(
                'belongsTo' => array('User'),
                'hasMany' => array('UserMessage'),
                )
            );
            $room = $this->UserRoom->find('first' , array(
                'conditions' => $conditions
            ));
            /*
            *ルームがなかった場合は、ルームを作成する
            */
            if(empty($room)){
                /*
                *ルームを作成する
                */
                $new_room_info['user_id'] = $this->request->data['id'];
                $new_room_info['partner_id'] = $this->request->data['partner_id'];
                $this->UserRoom->create();
                $new_room = $this->UserRoom->save($new_room_info);
                /*
                *メッセージのルームidを変数に格納する
                */
                $this->request->data['room_id'] = $this->UserRoom->getLastInsertID();
                /*
                *saveに失敗したらエラーメッセージ
                */
                if(empty($new_room)){
                    $message = array('result' => 'error' , 'detail' => 'NewRoomSaveError');
                    $this->set(array('message' => $message, '_serialize' => array('message')));   
                    return;
                }
            }
            /*
            *メッセージを検索するidを指定する
            */
            if(!empty($room)){
                $this->request->data['room_id'] = $room['UserRoom']['id'];
            }
            /*
            *メッセージを保存する
            */
            $message_info['message'] = $this->request->data['message'];
            $message_info['user_id'] = $this->request->data['id'];
            $message_info['user_room_id'] = $this->request->data['room_id'];

            $this->UserMessage->create();
            $message_info = $this->UserMessage->save($message_info);
            /*
            *saveに失敗したらエラーメッセージ
            */
            if(empty($message_info)){
                $message = array('result' => 'error' , 'detail' => 'UserMessageSaveError');
                $this->set(array('message' => $message, '_serialize' => array('message')));   
                return;
            }
            /*
            *ルームのmodifiedをupdateする
            */
            if(!empty($message_info)){
                $this->UserRoom->id = $this->request->data['room_id'];
                $this->UserRoom->saveField('modified', date('Y-m-d H:i:s'));
            }
        }
        /*
        *ルームidからメッセージを検索する
        */
        $this->UserRoom->unbindModel(array(
            'belongsTo' => array('User')
            )
        );
        $messages = $this->UserRoom->find('first', array(
            'conditions' => array(
                'UserRoom.id' => $this->request->data['room_id']
            ),
            'recursive' => 2,
            'order' => array('created' => 'DESC')
        ));
        $message = array('result' => 'success' , 'chat_data' => $messages);
        $this->set(array('message' => $message, '_serialize' => array('message')));
    } 

    public function report(){
        /*
        *リクエストがない場合のエラー
        */
        if(empty($this->request->data)){
            $message = array('result' => 'error' , 'detail' => 'RequestErrpr');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *現在の状況を調べる
        */
        $this->User->unbindModel(array(
            'hasMany' => array('UserImage' , 'UserLocation' , 'UserMessage'),
            )
        );
        $user = $this->User->find('first' , array(
            'conditions' => array('id' => $this->request->data['id'])
        ));
        /*
        *レポートカウントを増やす
        */
        $user['User']['report_count'] = $user['User']['report_count'] + 1;
        /*
        *ユーザーのカウントを保存する
        */
        $this->User->id = $user['User']['id'];
        $flg_update_user = $this->User->saveField('report_count', $user['User']['report_count']);
        if(empty($flg_update_user)){
            $message = array('result' => 'error' , 'detail' => 'ReportCountUpdateError');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        /*
        *メッセージにレポートフラグをつける
        */
        $this->UserMessage->id = $this->request->data['message_id'];
        $flg_update_message = $this->UserMessage->saveField('report_flg', 'true');
        if(empty($flg_update_message)){
            $message = array('result' => 'error' , 'detail' => 'MessageUpdateError');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }
        $message = array('result' => 'success' , 'user' => $flg_update_user ,'message' => $flg_update_message);
        $this->set(array('message' => $message, '_serialize' => array('message')));
    }


}
