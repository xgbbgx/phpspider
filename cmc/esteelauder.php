<?php
/**
 * 雅思兰黛
 */
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;
use phpspider\core\db;
use phpspider\core\image;

/* Do NOT delete this comment */
/* 不要删除这段注释 */
$configs = array(
    'name' => 'esteelauder',
    'tasknum' => 1,
    'log_show' => true,
    'domains' => array(
        'www.esteelauder.com.cn'
    ),
    'scan_urls' => array(
        "https://www.esteelauder.com.cn/products/634/product-catalog",//唇膏
    ),
    'list_url_regexes' => array(
        "https://www.esteelauder.com.cn/products/634/product-catalog",//唇膏
    ),
    'content_url_regexes' => array(
        "/product/634/[0-9a-zA-Z\_\-\/]+",
    ),
    'db_config' => array(
        'host'  => 'localhost',
        'port'  => 3306,
        'user'  => 'root',
        'pass'  => 'root',
        'name'  => 'spider_dev',
        'charset'=>'utf8',
    ),
    'fields' => array(
        // product
        array(
            'name' => "product_data",
            'selector' => "@var page_data =(.*?)</script>@",
            'selector_type' => 'regex',
            'required' => true,
        ),
    ),
    //爬虫爬取网页所使用的浏览器类型.随机浏览器类型，用于破解防采集
    'user_agent' => array(
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36",
        "Mozilla/5.0 (iPhone; CPU iPhone OS 9_3_3 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13G34 Safari/601.1",
        "Mozilla/5.0 (Linux; U; Android 6.0.1;zh_cn; Le X820 Build/FEXCNFN5801507014S) AppleWebKit/537.36 (KHTML, like Gecko)Version/4.0 Chrome/49.0.0.0 Mobile Safari/537.36 EUI Browser/5.8.015S",
    ),
    //爬虫爬取网页所使用的伪IP。随机伪造IP，用于破解防采集
    'client_ip' => array(
        '192.168.0.2',
        '192.168.0.3',
        '192.168.0.4',
        '192.168.0.6',
        '192.168.0.7',
        '192.168.0.8',
    ),
);

$spider = new phpspider($configs);


$spider->on_start = function($phpspider)
{
    $db_config = $phpspider->get_config("db_config");
    db::set_connect('default', $db_config);
    db::_init();
};

$spider->on_extract_field = function($fieldname, $data, $page)
{
    if ($fieldname == 'product_data')
    {
    	$jsData=json_decode(trim($data),true);
    	$prductA=empty($jsData['catalog-spp']['products'][0]) ?'':$jsData['catalog-spp']['products'][0];
    	if(empty($prductA['PRODUCT_ID'])){
    		return $data;
    	}
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
    			'default_sku_id'=>$prductA['defaultSku']['SKU_ID']
    	];
    	$skusA=empty($prductA['skus']) ? []:$prductA['skus'];
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
    	$data=[
    		'product'=>$product,
    		'sku'=>$skus
    	];
    }
    return $data;
};

$category = array(
    '眉笔' => '161',
    '唇彩' => '174',
    '唇线笔'=>'176'
);

$spider->on_extract_page = function($page, $data) use ($category)
{
	$source='estee_lauder';//来源
	$brandId='1';//品牌
	
    $product=$data['product_data']['product'];
    $sku=$data['product_data']['sku'];
    $categoryId=161;
    $productId=trim($product['product_id']);
    if(empty($productId)){
    	return 'Null Product ID';
    }
    $sql = "Select Count(*) As `count` From `t_product` Where product_id='{$productId}'";
    $row = db::get_one($sql);
    if (!$row['count'])
    {
        $skuDir='/uploads/product/sku/'.date('Y').'/'.date('m').'_'.date('d').'/'.$categoryId.'/';
        $productDir='/uploads/product/product/'.date('Y').'/'.date('m').'_'.date('d').'/'.$categoryId.'/';
        $shadeDir='/uploads/product/shade/'.date('Y').'/'.date('m').'_'.date('d').'/'.$categoryId.'/';
        $url='https://www.esteelauder.com.cn';
        $rating=0;
        $skuDefalut=0;
 		
        $pCover=[];
        if($product['cover']){
            if(count($product['cover'])>1){
                foreach ($product['cover'] as $p){
                	$pCover[]=image::saveFile($productDir,$url.$p);
                }
            }else{
            	$pCover[]=image::saveFile($productDir,$url.$product['cover']);
            }
        }
        $productData=[
        		'product_id'=>$productId,
        		'name'=>$product['name'],
        		'name_en'=>$product['name_en'],
        		'detail'=>$product['detail'],
        		'cover'=>json_encode($pCover),
        		//'product_feature'=>json_encode($product['product_desc'],JSON_UNESCAPED_UNICODE),
        		'usage'=>empty($product['usage'])?'':$product['usage'],
        		'effect'=>empty($product['effect'])?'':$product['effect'],
        		'ingredients'=>empty($product['ingredients'])?'':$product['ingredients'],
        		'skin_type'=>empty($product['skin_type'])?'':$product['skin_type'],
        		'counter'=>empty($product['counter'])?'':$product['counter'],
        		'source'=>$source,
        		'source_url'=>$product['url'],
        		'default_sku_id'=>$product['default_sku_id'],
        		'brand_id'=>$brandId
        ];
        $defaultSkuId=$product['default_sku_id'];
        $productId=db::insert("t_product", $productData);
        if($productId){
        	foreach ($sku as $s){
        		
        		$sql = "Select id From `t_shade` Where name='{$s['shade_name']}'";
        		$shadeArr = db::get_one($sql);
        		if(empty($shadeArr)){
	        		$shadeData=[
	        			'name'=>$s['shade_name'],
	        			'hex'=>$s['hex'],
	        				'cover'=>image::saveFile($shadeDir,$url.$s['shade_cover']),
	        			'description'=>$s['shade_description'],
	        			'color_family'=>$s['color_family'],
	        			'smoosh_design'=>$s['smoosh_design'],
	        			'intensity'=>$s['intensity']
	        		];
	        		$shadeId=db::insert("t_shade", $shadeData);
        		}else{
        			$shadeId=$shadeArr['id'];
        		}
        		$sCover=[];
        		if($s['cover']){
        			if(is_array($s['cover'])){
        				foreach ($s['cover'] as $p){
        					$sCover[]=image::saveFile($skuDir,$url.$p);
        				}
        			}else{
        				$sCover[]=image::saveFile($skuDir,$url.$s['cover']);
        			}
        		}
        		$skuData=[
        				'product_id'=>$productId,
        				'name'=>$s['name'],
        				'cover'=>json_encode($sCover),
        				'price'=>$s['price'],
        				'currency'=>$s['currency'],
        				'size'=>$s['size'],
        				'unit'=>$s['unit'],
        				'shade_id'=>$shadeId,
        				'is_default'=>($s['name']==$product['default_sku_id']) ?1:0
        		];
        		$skuId=db::insert("t_sku", $skuData);
        	}
        }
    }
    return $data;
};

$spider->start();
