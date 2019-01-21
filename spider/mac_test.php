<?php
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;
use phpspider\core\requests;
use phpspider\core\selector;
use phpspider\core\db;
use phpspider\core\image;
set_time_limit(0); 
    $url = "https://www.maccosmetics.com.cn/product/13854/310/matte-lipstick#/shade/Candy_Yum-Yum";
    $url='https://www.maccosmetics.com.cn/product/13854/37620/retro-matte-liquid-lipcolour#/shade/Dance_with_Me';
    $url='https://www.maccosmetics.com.cn/product/13854/310/matte-lipstick#/shade/Antique_Velvet';
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
    $regex='@var page_data =(.*?)</script>@'; 
    $jsRes=selector::select($html, $regex,'regex');
    //print_r($jsRes);
    $jsData=json_decode(trim($jsRes),true);
    $prductA=$jsData['consolidated-products']['products'][0];
    $productDesc=[
        'description'=>$prductA['DESCRIPTION'],
        'claims'=>$prductA['CLAIMS'],
        'usage'=>$prductA['PRODUCT_USAGE'],
        'short_desc'=>$prductA['SHORT_DESC'],
    ];
    $buzzword=explode('，', $prductA['PRODUCT_BUZZWORDS']);
    $category=@$prductA['category']['CATEGORY_NAME'];
    $product=[
        'product_id'=>$prductA['PRODUCT_ID'],
        'title'=>$prductA['SUB_LINE'],
        'product_name_en'=>$prductA['PROD_RGN_NAME'],
        'buzzword'=>($buzzword) ? implode(',', $buzzword):'',
        'cover'=>empty($prductA['LARGE_IMAGE']['0']) ?'':$prductA['LARGE_IMAGE']['0'],
        'avg_rating'=>$prductA['AVERAGE_RATING'],
        'product_desc'=>$productDesc,
        'url'=>$prductA['url'],
        'category'=>$category,
        'sku_default'=>$prductA['defaultSku']['SKU_ID'],
        'skus'=>[]
    ];
    $skuData=[];
    $skus=$prductA['skus'];
    foreach ($skus as $p){
        $size='';$unit='';
        if($p['PRODUCT_SIZE']){
            $pSize=explode(' ', $p['PRODUCT_SIZE']);
            if(count($pSize)>1){
                $size=intval(trim($pSize[0]));
                $unit=trim($pSize[1]);
            }else{
                $size=intval(trim($p['PRODUCT_SIZE']));
                $unit=trim(str_replace($size, '', $p['PRODUCT_SIZE']));
            }
        }
        $cover='';$image='';
        if($p['LARGE_IMAGE'] && is_array($p['LARGE_IMAGE'])){
            $cover=$p['LARGE_IMAGE'][0];
            $image=$p['LARGE_IMAGE'];
        }
        $skuData[]=[
            'sku_id'=>$p['SKU_BASE_ID'],
            'sku_name'=>$p['SKU_ID'],
            'size'=>$size,
            'unit'=>$unit,
            'cover'=>$cover,
            'image'=>$image,
            'color'=>$p['HEX_VALUE_STRING'],
            'upc_code'=>$p['UPC_CODE'],
            'price'=>doubleval(str_replace('¥', '', $p['formattedPrice'])),
            'finish'=>$p['FINISH'],
            'shade_name'=>$p['SHADENAME'],
            'shade_description'=>$p['SHADE_DESCRIPTION'],
            'shade_code'=>$p['PRODUCT_CODE'],
            'shade_cover'=>$p['IMAGE_SMOOSH'],
            'shade_color_family'=>$p['ATTRIBUTE_COLOR_FAMILY'],
        ];
    }
    $product['skus']=$skuData;
    print_r($product);
    exit;
    if($product){
        $db_config=array(
            'host'  => 'localhost',
            'port'  => 3306,
            'user'  => 'root',
            'pass'  => 'root',
            'name'  => 'spider_dev',
            'charset'=>'utf8',
        );
        db::set_connect('default', $db_config);
        db::_init();
        $productId=trim($product['product_id']);
        $sql = "Select Count(*) As `count` From `t_mac_product` Where product_id='{$productId}'";
        $row = db::get_one($sql);
        if (!$row['count'])
        {
            $skuDir='/uploads/product/sku/'.date('Y').'/'.date('m').'/'.date('d').'/';
            $shadeDir='/uploads/product/shade/'.date('Y').'/'.date('m').'/'.date('d').'/';
            $url='https://www.maccosmetics.com.cn';
            $categoryId=11;
            $rating=$product['avg_rating'];
            $skuDefalut=$product['sku_default'];
            $productData=[
                'product_id'=>$productId,
                'product_name'=>$product['title'],
                'product_cover'=>image::saveFile($skuDir,$url.$product['cover']),
                'product_feature'=>json_encode($product['product_desc'],JSON_UNESCAPED_UNICODE),
                'buzzword'=>$product['buzzword'],
                'source'=>'maccosmetics',
                'source_url'=>$product['url'],
                'avg_rating'=>$rating,
                'category_id'=>$categoryId
            ];
                 
            $p_id=db::insert("t_mac_product", $productData);
            if($p_id && isset($product['skus'])){
                foreach($product['skus'] as $sku){
                    $shadeId=0;
                    $shadeCode=$sku['shade_code'];
                    $sql = "Select id From `t_mac_product_shade` Where code='{$shadeCode}'";
                    $dbShade = db::get_one($sql);
                    if($dbShade['id']){
                        $shadeId=$dbShade['id'];
                    }else{
                        $shadeData=[
                            'name'=>$sku['shade_name'],
                            'name_en'=>$sku['shade_name'],
                            'description'=>$sku['shade_description'],
                            'color_family'=>$sku['shade_color_family'],
                            'code'=>$shadeCode,
                            'cover'=>image::saveFile($shadeDir,$url.$sku['shade_cover']),
                            'color'=>$sku['color'],
                            'color_family'=>$sku['shade_color_family'],
                        ];
                        $shadeId=db::insert("t_mac_product_shade", $shadeData);
                    }
                    $isDefault=0;
                    if($skuDefalut==$sku['sku_name']){
                        $isDefault=1;
                    }
                    $cover='';$image=[];
                    if($sku['image']){
                        $i=0;
                        foreach ($sku['image'] as $img){
                            $imgUrl=image::saveFile($skuDir,$url.$img);
                           if($i==0){ 
                               $cover=$imgUrl;
                           }
                           $image[]=$imgUrl;
                           $i++;
                        }
                    }
                    $skuData=[
                        'product_id'=>$p_id,
                        'name'=>$sku['sku_name'],
                        'cover'=>$cover,
                        'image'=>empty($image) ?'':json_encode($image),
                        'rating'=>$rating,
                        'color'=>$sku['color'],
                        'sku_id'=>$sku['sku_id'],
                        'upc_code'=>$sku['upc_code'],
                        'size'=>$sku['size'],
                        'unit'=>$sku['unit'],
                        'price'=>$sku['price'],
                        'finish'=>$sku['finish'],
                        'is_default'=>$isDefault,
                        'shade_id'=>$shadeId
                    ];
                    db::insert("t_mac_product_sku", $skuData);
                }
            }
        }
    }



