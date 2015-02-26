<?php
 
class GurunabiComponent extends Component {

    //アクセスするURLを作成するメソッド
    public function make_gnaviapi_url($lati,$long) {
    	//urlの指定
        $api = 'http://api.gnavi.co.jp/ver1/RestSearchAPI/?';
        //キーの指定
        $key = '6f13d54e08f1c5397b1aaa3091cab074';
        //レンジの指定（半径2000m以内）
        $range = 4;
        //jsonに設定
        $format = 'xml';
        //世界緯度系に設定
        $coordinates_mode = 2;
        //小業態コード
        $category_s = 'RSFST18014';
        //ヒット件数
        $hit_per_page = 99;
        //アクセスするurlの指定
        $query = $api.'keyid='.$key.'&latitude='.$lati.'&longitude='.$long.'&range='.$range.'&format='.$format.'&hit_per_page='.$hit_per_page.'&coordinates_mode='.$coordinates_mode.'&category_s='.$category_s;
        //値を返す
        return $query;
    }

    //xml形式のデータを連想配列に変更する
    public function parse_xml_to_array($query) {
			//ぐるなびURLにアクセスしてxmlを読み込む
			$xml = simplexml_load_file($query);
			//xml形式のデータを連想配列に変換する
			$xml = get_object_vars($xml);
			//残ったSimpleXMLElementオブジェクトをarrayにキャストする
			$data = json_decode(json_encode($xml), true);
			//値を返す
			return $data;
    }

    //連想配列から特定の値を取り出す
    public function get_rest_info($restaurant_data) {

		foreach($restaurant_data['rest'] as $i=>$val) {
			//お店の名前
		  	$info[$i]['name'] = $restaurant_data['rest'][$i]['name'];
		  	//画像
		  	$info[$i]['image_url'] = $restaurant_data['rest'][$i]['image_url']['shop_image1'];
		  	//URL
		  	$info[$i]['url'] = $restaurant_data['rest'][$i]['url'];
		  	//電話番号
		  	$info[$i]['tel_number'] = $restaurant_data['rest'][$i]['tel'];
		  	//住所
		  	$info[$i]['address'] = $restaurant_data['rest'][$i]['address'];
		  	//説明分
		  	$info[$i]['pr'] = $restaurant_data['rest'][$i]['pr']['pr_short'];
		  	//カテゴリー
		  	$info[$i]['category'] = $restaurant_data['rest'][$i]['category'];
		  	//予算
		  	$info[$i]['budget'] = $restaurant_data['rest'][$i]['budget'];
		  	//緯度
		  	$info[$i]['latitude'] = $restaurant_data['rest'][$i]['latitude'];
		  	//経度
		  	$info[$i]['longitude'] = $restaurant_data['rest'][$i]['longitude'];
		}
		//値を返す
		return $info;
    }

    //prから不要なデータを削除する
    public function delete_disuse_data_from_pr($info) {

		foreach($info as $i=>$val) {

			if (strstr($info[$i]['pr'], "〒") == true || strstr($info[$i]['pr'], "TELL") == true) {
				$info[$i]['pr'] = null;
			}

		}
		//値を返す
		return $info;
    }   

    //mapURLを作成
    public function get_map_url($info) {

		foreach($info as $i=>$val) {
		  	//URL（携帯用）
		  	list($domain[$i],$dir_01[$i],$dir_02[$i],$dir_03[$i],$dir_04[$i]) = explode("/",$info[$i]['url']);
		  	$info[$i]['map_url'] = $domain[$i].'//'.$dir_02[$i].'/'.$dir_03[$i].'/map/';
		}
		//値を返す
		return $info;
    }

    //画像URLのあるデータとないデータを分ける
    public function divide_image_exist($info) {
    	//画像URLのあるデータとないデータを分ける
		foreach($info as $i=>$val) {

			//URLがあるがない場合
		  	if(empty($info[$i]['image_url'])) {
		  		$info['image_not_exist'][$i] = $info[$i];
		  		unset($info[$i]);
		  	} elseif (isset($info[$i]['image_url'])) {
				//URLに接続出来ない場合
				$get_contents[$i] = @file_get_contents($info[$i]['image_url']);
				if($get_contents[$i] == false) {
					$info['image_not_exist'][$i] = $info[$i];
					unset($info[$i]);
				} else {
					$info['image_exist'][$i] = $info[$i];
					unset($info[$i]);
				}
									
		  	} 
		}
		//配列をつめる
		$info['image_exist'] = array_values($info['image_exist']); 
		$info['image_not_exist'] = array_values($info['image_not_exist']); 
		//値を返す
		return $info;
    }

}