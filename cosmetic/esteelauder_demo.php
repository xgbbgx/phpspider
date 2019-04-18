<?php
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\requests;
use phpspider\core\selector;
use phpspider\core\image;
set_time_limit(0);
/**$domainUrl='https://www.esteelauder.com.cn';
$p='/media/export/cms/products/226x311/el_sku_RJ1801_226x311_0.jpg';
$productDir='/uploads/product/product/'.date('Y').'/'.date('m').'_'.date('d').'/';
$a=image::saveFile($productDir,$domainUrl.$p);
print_r($a);
exit();*/
//$skuDir='/uploads/product/'.date('Y').'/'.date('m').'_'.date('d').'/';
//echo image::saveFile($skuDir,'https://www.esteelauder.com.cn/media/export/cms/products/558x768/el_sku_YLJ901_558x768_0.jpg');
//exit;
$url='https://www.esteelauder.com.cn/product/684/59757/product-catalog/micro-algae';
$html = requests::get($url);

$regex='@var page_data =(.*?)</script>@';
$jsRes=selector::select($html, $regex,'regex');

$jsData=json_decode(trim($jsRes),true);
$prductA=$jsData['catalog-spp']['products'][0];
print_r($prductA);
$product=[
		'parent_id'=>$prductA['PARENT_CAT_ID'],
		'product_id'=>$prductA['PRODUCT_ID'],
		'name'=>$prductA['PROD_RGN_SUBHEADING'],
		'name_en'=>$prductA['PROD_RGN_NAME'],
		'detail'=>$prductA['PRODUCT_DETAILS_LONG'],
		'cover'=>$prductA['LARGE_IMAGE'],
		'url'=>$prductA['url'],
		'effect'=>$prductA['ATTRIBUTE_DESC_1'],
		'usage'=>$prductA['ATTRIBUTE_DESC_2'],
		'ingredients'=>$prductA['ATTRIBUTE_DESC_3'],
		'skin_type'=>$prductA['ATTRIBUTE_DESC_4'],
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
		$unit='';$size=0;$largess='';
		if($s['PRODUCT_SIZE']){
			if(preg_match('/(\d+[\.\d]{1,4})+(ml|g|毫升|克|mg|毫克|片|只|支)+/',$s['PRODUCT_SIZE'],$matchs1)){
				//$product_size_1=doubleval($s['PRODUCT_SIZE']);
				//$unit=trim(str_replace($product_size_1, '', $s['PRODUCT_SIZE']));
				//$size=$product_size_1;
				print_r($matchs1);
				$size=empty($matchs1['1']) ?'':doubleval($matchs1['1']);
				$unit=empty($matchs1['2']) ?'':$matchs1['2'];
			}
			$largess=preg_replace('/'.$size.$unit.'/', '', $s['PRODUCT_SIZE'],1);
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
				'intensity'=>$s['INTENSITY'],
				'largess'=>$largess
		];
	}
}
print_r($skus);
exit;