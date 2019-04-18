<?php
namespace phpspider\core;
class image{
    public static function get_url_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//禁止直接显示获取的内容 重要
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); //5秒钟超时
        $result = curl_exec($ch);
        if(curl_errno($ch))
        {
            return false;
        }
        return $result;
    }
    public static function saveFile($dir,$url,$fileName=''){
        $imgUrl = htmlspecialchars($url);
        $imgUrl = str_replace("&amp;", "&", $imgUrl);
        
        //http开头验证
        if (strpos($imgUrl, "http") !== 0) {
            echo ("ERROR_HTTP_LINK");
            return $url;
        }
        
        preg_match('/(^https*:\/\/[^:\/]+)/', $imgUrl, $matches);
        $host_with_protocol = count($matches) > 1 ? $matches[1] : '';
        
        // 判断是否是合法 url
        if (!filter_var($host_with_protocol, FILTER_VALIDATE_URL)) {
            echo ("INVALID_URL");
            return $url;
        }
        
        preg_match('/^https*:\/\/(.+)/', $host_with_protocol, $matches);
        $host_without_protocol = count($matches) > 1 ? $matches[1] : '';
        
        // 此时提取出来的可能是 ip 也有可能是域名，先获取 ip
        $ip = gethostbyname($host_without_protocol);
        // 判断是否是私有 ip
        if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
            echo ("INVALID_IP");
            return $url;
        }
        
        //获取请求头并检测死链
        $heads = get_headers($imgUrl, 1);
        if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
            echo ("ERROR_DEAD_LINK");
            return $url;
        }
        //格式验证(扩展名验证和Content-Type验证)
        $fileType = strtolower(strrchr($imgUrl, '.'));
        $imgType=[".png", ".jpg", ".jpeg", ".gif", ".bmp"];
        if (!in_array($fileType, $imgType) || !isset($heads['Content-Type']) || !stristr($heads['Content-Type'], "image")) {
            echo ("ERROR_HTTP_CONTENTTYPE");
            return $url;
        }
        $absDir=dirname(__DIR__). '/../../my-data/cosmetic'.$dir;
        if(!is_dir($absDir)){
            if(mkdir($absDir,0777,true)){
                
            }else{
                echo 'Not mkdir';
                return $url;
            }
        }
        if(empty($fileName)){
            $fileName=time().'_'.rand(10000,99999);
        }
        $filePath=$absDir.$fileName.$fileType;
        $img=self::get_url_contents($imgUrl);//获取图片
        if (file_put_contents($filePath, $img)) { //移动失败
            return $dir.$fileName.$fileType;
        }
    }
}