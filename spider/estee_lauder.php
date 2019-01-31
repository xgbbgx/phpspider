<?php
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;
use phpspider\core\requests;
use phpspider\core\selector;
use phpspider\core\db;
use phpspider\core\image;
set_time_limit(0);
$url='https://www.esteelauder.com.cn/product/634/29627/product-catalog/black-brown';
$html = requests::get($url);

/**
 // 选择器规则
 $selector = "//h3[contains(@class,'product__subline')]//h3";
 $selector1 ='@<h3 class="product__subline">(.*?)</h3>@';
 $selector2='.product__header > .product_header_details > a > h3';
 // 提取结果
 //$result = selector::select($html, $selector1,'regex');
 $result = selector::select($html, $selector2,'css');
 
 $titleRes= selector::select($html, '.product__header > .product_header_details > a > h3','css');
 print_r($titleRes);
 $desRes= selector::select($html, '@<div class="product__description-short product_rgn_name_below_subline">(.*?)</div>@','regex');
 print_r($desRes);
 $infoRes=selector::select($html, '.product__description--compact > .block__content','css');
 print_r($infoRes);*/

$product_name_en=selector::select($html, '.product-full__title','css');
$product_name=selector::select($html, '.product-full__subtitle','css');
$product_cover=selector::select($html, '.product-full__image > img','css');
$product_detail=selector::select($html, '.spp-product__details-description','css');
$product_url=selector::select($html, '.spp-product__mini-bag-image-container','css');
$product_attr=selector::select($html, '.spp-product__details-attribute > p','css');
$product_size=selector::select($html, '.product-full__price-text','css');
$product_price=selector::select($html, '.product-full__price-text > .product-full__price','css');

$product_use='';$product_effect='';$product_skin_type='';$product_counter='';
if($product_attr){
    $product_use=@$product_attr['0'];
    $product_effect=@$product_attr['1'];
    $product_skin_type=@$product_attr['2'];
    $product_counter=@$product_attr['3'];
}
$product_size_arr=explode(' ', $product_size);
$product_unit=empty($product_size_arr[1]) ?'':$product_size_arr[1];
$product_size=empty($product_size_arr[0]) ?'':$product_size_arr[0];
if(count($product_size_arr)>1){
    if( preg_match('/\\d+/',$product_size,$matchs1) == 1){
        $product_unit=empty($product_size_arr[1]) ?'':$product_size_arr[1];
        $product_size=empty($product_size_arr[0]) ?0:$product_size_arr[0];
    }else{
        $product_unit=empty($product_size_arr[2]) ?'':$product_size_arr[2];
        $product_size=empty($product_size_arr[1]) ?0:$product_size_arr[1];
    }
    if($product_size){
        if(is_numeric(trim($product_size))){
            
        }else{
            $product_size_1=intval($product_size);
            $product_unit=trim(str_replace($product_size_1, '', $product_size));
            $product_size=$product_size_1;
        }
    }
}else{
    $product_unit=0;
    $product_size='';
}
if(is_numeric($product_size)){
    $product_unit=0;
}
if(in_array($product_unit, ['ml','毫升','g','克','mg','毫克','片','只','支'])){
    $product_unit='';
}
$reg1="/<a href=\"(.*?)\">(.*?)/i";
preg_match_all($reg1,$product_url,$aarray);
$product_url=empty($aarray['1']['0']) ? '':$aarray['1']['0'];
$product=[
    'product_name'=>$product_name,
    'product_name_en'=>$product_name_en,
    'product_cover'=>$product_cover,
    'product_detail'=>$product_detail,
    'product_url'=>$product_url,
    'product_use'=>$product_use,
    'product_effect'=>$product_effect,
    'product_skin_type'=>$product_skin_type,
    'product_counter'=>$product_counter,
    'product_size'=>$product_size,
    'product_unit'=>$product_unit,
    'product_price'=>$product_price,
];

print_r($product);
exit;



