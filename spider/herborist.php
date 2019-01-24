<?php
//佰草集
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\requests;
use phpspider\core\selector;
use phpspider\core\image;
set_time_limit(0);
$url='http://www.herborist.com.cn/makeup/lips/75300.html';
$html = requests::get($url);
$name=selector::select($html, '.product_shop > h2','css');
$info=selector::select($html, '.product_shop > .infor','css');
$info=trim(str_replace('&#13;', '', strip_tags($info)));
$effect=selector::select($html, '.product_shop > .product_effect','css');
$effect=trim(str_replace('&#13;', '', strip_tags($effect)));
$img=selector::select($html, '.product_image > img','css');
$price=selector::select($html, '.price-box > .regular-price > .price','css');
$p_currency='1';
$price=trim(str_replace('￥', '', $price));
$size=selector::select($html, '.guige','css');
$size=trim(str_replace('/', '', $size));
$images=selector::select($html, '.product_details_info_wrapper > img','css');
$product=[
    'name'=>$name,
    'info'=>$info,
    'effect'=>$effect,
    'img'=>$img,
    'price'=>$price,
    'size'=>$size,
    'images'=>json_encode($images)
];
print_r($product);
exit;



