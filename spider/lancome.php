<?php
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\requests;
use phpspider\core\selector;
set_time_limit(0);
$url='https://www.lancome.com.cn/item/LAN00202-005';
$html = requests::get($url);

$product_code=selector::select($html, '@itemstyle=\"(.*?)\"@','regex');

$product_name_en=selector::select($html, '.product-tit > h2','css');
$product_name=selector::select($html, '.product-tit > h1','css');
$product_cover=selector::select($html, '.events-master-scroll','css');
$product_detail=selector::select($html, '.product-introduction-box','css');
$product_desc=selector::select($html, '.tabs-container','css');
$product_price=selector::select($html, '.priceAndNum > .product-price','css');

$product_attr='';//selector::select($html, '.spp-product__details-attribute > p','css');
$product_use='';$product_effect='';$product_skin_type='';$product_counter='';
if($product_attr){
    $product_use=@$product_attr['0'];
    $product_effect=@$product_attr['1'];
    $product_skin_type=@$product_attr['2'];
    $product_counter=@$product_attr['3'];
}
$productCover=[];
if($product_cover){
    $pattern="/<img(.*?)data-cloudzoom=\"(.*?)\"\/>/i";
    if (preg_match_all($pattern, $product_cover,$arr)){
        $arrImg=empty($arr['2']) ? []:$arr['2'];
        foreach ($arrImg as $im){
            if (preg_match_all("/zoomImage: \'(.*?)\?/i", $im,$arr1)){
                $productCover[]=empty($arr1['1']['0']) ?'':$arr1['1']['0'];
            }
        }
    }
}
$product_name=trim(str_replace('&#13;', '', strip_tags($product_name)));
$product_detail=str_replace('&#13;', '', strip_tags($product_detail,'<div><br>'));
$product_detail=str_replace('产品简介', '',$product_detail);
$product_detail=trim(str_replace('查看更多', '',$product_detail));

$product_desc=str_replace('&#13;', '', strip_tags($product_desc,'<div><br>'));
$product_desc=trim(str_replace('&gt;', '',str_replace('查看更多', '',$product_desc)));
$product=[
    'product_name'=>$product_name,
    'product_name_en'=>trim($product_name_en),
    'product_cover'=>$productCover,
    'product_detail'=>$product_detail,
    'product_desc'=>$product_desc,
    'product_use'=>$product_use,
    'product_effect'=>$product_effect,
    'product_skin_type'=>$product_skin_type,
    'product_counter'=>$product_counter,
    'product_price'=>$product_price
];

exit;
