<?php
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;
use phpspider\core\requests;
use phpspider\core\selector;
use phpspider\core\db;
use phpspider\core\image;
set_time_limit(0);

//$skuDir='/uploads/product/'.date('Y').'/'.date('m').'_'.date('d').'/';
//echo image::saveFile($skuDir,'https://www.esteelauder.com.cn/media/export/cms/products/558x768/el_sku_YLJ901_558x768_0.jpg');
//exit;
$url='https://www.esteelauder.com.cn/product/634/42965/product-catalog/brow-multi-tasker';
$html = requests::get($url);
 
$regex='@var page_data =(.*?)</script>@';
$jsRes=selector::select($html, $regex,'regex');

$jsData=json_decode(trim($jsRes),true);
$prductA=$jsData['catalog-spp']['products'][0];
print_r($prductA);
$product=[
		'product_id'=>$prductA['PRODUCT_ID'],
		'name'=>$prductA['PROD_RGN_SUBHEADING'],
		'name_en'=>$prductA['PROD_RGN_NAME'],
		'detail'=>$prductA['PRODUCT_DETAILS_LONG'],
		'cover'=>$prductA['LARGE_IMAGE'],
		'url'=>$prductA['url'],
		'effect'=>$prductA['ATTRIBUTE_DESC_1'],
		'usage'=>$prductA['ATTRIBUTE_DESC_2'],
		'ingredients'=>$prductA['ATTRIBUTE_DESC_3'],
		'skin_type'=>$prductA['ATTRIBUTE_LABEL_4'],
		'counter'=>$prductA['ATTRIBUTE_DESC_5'],
		'default_id'=>$prductA['defaultSku']['SKU_ID']
];
print_r($product);
$skusA=$prductA['skus'];
$skus=[];
if($skusA){
	foreach ($skusA as $s){
		$currency=1;//RMB
		$price=0;
		if($s['formattedPrice']){
			$price=doubleval(trim(str_replace('¥', '', $s['formattedPrice'])));
		}
		$unit='';$size=0;
		if($s['PRODUCT_SIZE']){
			if(preg_match('/\d+(\.\d{1,4})?(ml|g|毫升|克|mg|毫克|片|只|支)+/',$s['PRODUCT_SIZE'],$matchs1)){
				$product_size_1=doubleval($s['PRODUCT_SIZE']);
				$unit=trim(str_replace($product_size_1, '', $s['PRODUCT_SIZE']));
				$size=$product_size_1;
			}
		}
		$skus[]=[
			'name'=>$s['SKU_ID'],
			'upc_code'=>$s['UPC_CODE'],
			'cover'=>$s['XL_IMAGE'],
			'price'=>$price,
			'currency'=>$currency,
			'size'=>$size,
			'unit'=>$unit,
			'hex'=>$s['HEX_VALUE_STRING'],
			'shade_name'=>$s['SHADENAME'],
			'shade_cover'=>$s['XL_SMOOSH'],
			'shade_description'=>$s['SHADE_DESCRIPTION'],
			'color_family'=>$s['ATTRIBUTE_COLOR_FAMILY'],
			'smoosh_design'=>$s['SMOOSH_DESIGN'],
			'intensity'=>$s['INTENSITY']
		];
	}
}
print_r($skus);
exit;
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
print_r($product_size_arr);
if(count($product_size_arr)>1){
	$product_unit=empty($product_size_arr[1]) ?'':$product_size_arr[1];
	$product_size=empty($product_size_arr[0]) ?'':$product_size_arr[0];
	if( preg_match('/d+/',$product_size,$matchs1) == 1){
	}else{
		if(preg_match('/^[0-9]+(ml|g|毫升|克|mg|毫克|片|只|支)+/',$product_size,$matchs1)){
			$product_size_1=intval($product_size);
			$product_unit=trim(str_replace($product_size_1, '', $product_size));
			$product_size=$product_size_1;
		}else{
			$product_unit=empty($product_size_arr[2]) ?'':$product_size_arr[2];
			$product_size=empty($product_size_arr[1]) ?0:$product_size_arr[1];
		}
	}
	/**if($product_size){
		if(is_numeric(trim($product_size))){
			
		}else{
			$product_size_1=intval($product_size);
			$product_unit=trim(str_replace($product_size_1, '', $product_size));
			$product_size=$product_size_1;
		}
	}*/
	$product_unit=trim(strtolower($product_unit));
	$product_unit = preg_replace("/(\s|\ \;|　|\xc2\xa0)/","",$product_unit);
	if(in_array($product_unit,  ['ml','毫升','g','克','mg','毫克','片','只','支'])){
	}else{
		$product_unit='';
		$product_size=0;
	}
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



