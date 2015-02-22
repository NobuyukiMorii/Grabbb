<?php
class WebrootFileDirComponent extends Component
{
    public function WebrootFileDir(){

        $dmain = $_SERVER['HTTP_HOST'];
        $fullpath = $_SERVER['SCRIPT_FILENAME'];
    
        $pattern = "'/(.*" .$dmain .")/'";
        $domainpath = preg_replace($pattern , '' , $fullpath);
    
        $filepath = explode( '/', $domainpath);
        $dircount = count($filepath);
        $target = $dircount - 2;

        if($target <= 0){
                $path = $dmain;
            }else{
                $WebrootFileDir = '';      
                for($i = 0; $i < $target; $i++){
                    $WebrootFileDir .= '/';
                    $WebrootFileDir .= $filepath[$i];
                }
            $WebrootFileDir = '/' . ltrim($WebrootFileDir, '/').'/webroot';
        }
        return $WebrootFileDir;
    }
}