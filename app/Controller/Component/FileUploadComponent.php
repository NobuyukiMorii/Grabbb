<?php
class FileUploadComponent extends Component
{

    public function UploadValidation($MAX_FILE_SIZE){
        //アップロードエラーを検出する
        if($_FILES['image']['error'] != UPLOAD_ERR_OK){
            $message = array('result' => 'errror' , 'detail' => 'UploadError');
        }
        //サイズエラーを検出する
        $size = filesize($_FILES['image']['tmp_name']);
        if(!$size || $size > $MAX_FILE_SIZE){
            $message = array('result' => 'errror' , 'detail' => 'SizeErrror');
        }
        if(isset($message)){
            return $message;
        } else {
            return null;
        }
    }

    public function ImageTypeValidation($imagesize){
        //画像の拡張子のエラーを検出する
        switch ($imagesize['mime']) {
            case 'image/gif':
                break;
            case 'image/jpeg':
                break;
            case 'image/png':
                break;
            default:
                $message = array('result' => 'errror' , 'detail' => 'TypeErrror');
                exit;
        }
        if(isset($message)){
            return $message;
        } else {
            return null;
        }
    }

    public function GetImageType($imagesize){
        //画像の拡張子を検出する
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
        }
        if(isset($ext)){
            return $ext;
        } else {
            return null;
        }
    }

    public function UploadToOriginalImageFolder($imageFilePath){
        //アップロードする
        $rs = move_uploaded_file($_FILES['image']['tmp_name'], $imageFilePath);
        //アップロードの判定
        if(!$rs){
            $message = array('result' => 'errror' , 'detail' => 'MoveUploadedFileErrror');
        }
        if(isset($message)){
            return $message;
        } else {
            return null;
        }
    }

    //画像をリサイズする
    public function MakeResizeImage($imageFilePath,$imageFileName,$imagesize,$THUMBNAIL_WIDTH){
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
        return $thumbImage;
    }

    public function UploadTHUMBNAILS_DIR($imagesize,$thumbImage , $THUMBNAILS_DIR , $imageFileName){
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
    }
}