<?php
// Controller/UsersController.php
class UsersController extends AppController {

    public $uses = array('User' , 'UserImage' , 'UserLocation' , 'UserMessage');

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
        $location_data['natural_id'] = null;
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
            $location_data['natural_id'] = $location_log['UserLocation']['id'];

            $flg = $this->UserLocation->save($location_data);  
            if($flg){
                $message = array('result' => 'success' , 'near_users' => $near_users , 'location_log_id' => $location_log['UserLocation']['id'] , 'latitude' => $location_data['latitude'], 'longitude' => $location_data['longitude']);
            } else {
                $message = array('result' => 'error' , 'detail' => 'UpdateError');
            }
        }
        
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
        *メッセージが入っていた場合はsaveする
        */
        if(isset($this->request->data['message'])){
            $data['message'] = $this->request->data['message'];
            $this->UserMessage->create();
            $this->UserMessage->save($data);
        }
        /*
        *過去の投稿を検索する
        */
        $PastConversation = $this->UserMessage->find('all' , array(
            'conditions' => array(
                'OR' =>
                    array(
                           'AND' => array(
                                          array('UserMessage.user_id' => $data['user_id']),
                                          array('UserMessage.partner_id' => $data['partner_id'])
                                    ),
                           'AND' => array(
                                          array('UserMessage.partner_id' => $data['partner_id'],
                                          array('UserMessage.user_id' => $data['user_id'])
                                    ),
                    ),
            )
        )));
        if(empty($PastConversation)) {
            $message = array('result' => 'errror' , 'detail' => 'PastConversation');
            $this->set(array('message' => $message, '_serialize' => array('message')));
            return;
        }
        $message = array('result' => 'success' , 'chat' => $PastConversation);
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
        $message = array('result' => 'success' , 'cafe' => $cafes);
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
                        array('NOT' => array('user_id' => $id))
                    ),
            )
        ));
        /*
        *ユーザーが範囲内にいなかった時
        */
        if(empty($users)){
            $message = array('result' => 'error' , 'detail' => 'NoUsers');
            $this->set(array('message' => $message, '_serialize' => array('message')));   
            return;
        }

        /*
        *緯度と経度を取得して、保存
        */
        $location_log = $this->UserLocation->find('first' , array(
            'conditions' => array('user_id' => $id)
        ));
        /*
        *user_locationにuser_idが存在しない時
        */
        unset($this->request->data['distance']);
        if(!$location_log){
            $this->UserLocation->create();
            $flg = $this->UserLocation->save($this->request->data);  
            if($flg){
                $message = array('result' => 'success' , 'latitude' => $this->request->data['latitude'], 'longitude' => $this->request->data['longitude'] , 'near_users' => $users);
            } else {
                $message = array('result' => 'error' , 'detail' => 'CreateError');
            }
           
        }
        /*
        *user_locationが存在する時
        */
        if($location_log){
            $this->UserLocation->id = $location_log['UserLocation']['id'];
            $location_data['natural_id'] = $location_log['UserLocation']['id'];
            $flg = $this->UserLocation->save($location_data);  
            if($flg){
                $message = array('result' => 'success' , 'latitude' => $this->request->data['latitude'], 'longitude' => $this->request->data['longitude'] , 'near_users' => $users);
            } else {
                $message = array('result' => 'error' , 'detail' => 'UpdateError');
            }
        }
        /*
        *メッセージを返す
        */       
        $this->set(array('message' => $message, '_serialize' => array('message')));

    }


}
