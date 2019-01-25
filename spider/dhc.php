<?php
//佰草集
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\requests;
use phpspider\core\selector;
set_time_limit(0);
$url='http://www.dhc.net.cn/gds/detail.jsp?gcd=22446';
$html = requests::get($url);
$info=selector::select($html, '.frabic-detail-big-box > table','css');
print_r($info);
preg_match_all('/<td[\d\D]*?>([\d\D]*?)<\/td>/i',$info,$arr);
print_r($arr);
exit;
$img=selector::select($html, '.frabic-detail-box > .frabic-detail-left > img','css');
$name=selector::select($html, '.frabic-detail-box > .frabic-detail-right > h3','css');
$info=selector::select($html, '.frabic-detail-big-box > table','css');
$productAttr=selector::select($html, '.detail-chose-box > .detail-chose3 > strong','css');
$productAttra=selector::select($html, '.detail-chose-box > .detail-chose2 > strong','css');


$product=[
    'name'=>$name,
    'info'=>$info,
    'img'=>$img,
    'attr'=>$productAttr,
    'attr_a'=>$productAttra,
    'attr_b'=>$productAttrb
];
print_r($product);
exit;