<?php
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;
use phpspider\core\db;
use phpspider\core\image;

/* Do NOT delete this comment */
/* 不要删除这段注释 */
$configs = array(
    'name' => 'Mac',
    'tasknum' => 1,
    //'multiserver' => true,
    'log_show' => true,
    //'save_running_state' => false,
    'domains' => array(
        'www.maccosmetics.com.cn'
    ),
    'scan_urls' => array(
        "https://www.maccosmetics.com.cn/products/13852",//唇膏
       // "https://www.maccosmetics.com.cn/products/13853",//唇彩
    ),
    'list_url_regexes' => array(
        "https://www.maccosmetics.com.cn/products/13852",//唇膏
    ),
    'content_url_regexes' => array(
        "/product/13852/\d+/[0-9a-zA-Z\.\_\-\/\#]+",
        //"/product/13853/\d+/[0-9a-zA-Z\.\_\-\/\#]+",
    ),
    //'export' => array(
    //'type' => 'db',
    //'table' => 'meinv_content',
    //),
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
            'name' => "product",
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
    ),
);

$spider = new phpspider($configs);


$spider->on_start = function($phpspider)
{
    $db_config = $phpspider->get_config("db_config");
    //print_r($db_config);
    //exit;
    // 数据库连接
    db::set_connect('default', $db_config);
    db::_init();
};

$spider->on_extract_field = function($fieldname, $data, $page)
{
    if ($fieldname == 'product')
    {
        $jsData=json_decode(trim($data),true);
        //print_r($jsData);
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
        $data=$product;
    }
    return $data;
};

$category = array(
    '唇膏' => '173',
    '唇彩' => '174',
    '唇线笔'=>'176'
);

$spider->on_extract_page = function($page, $data) use ($category)
{
    $product=empty($data['product']) ? []:$data['product'];
    if (!isset($category[trim($product['category'])]))
    {
        return false;
    }else{
        $categoryId=$category[trim($product['category'])];
    }
    $productId=trim($product['product_id']);
    $sql = "Select Count(*) As `count` From `t_mac_product` Where product_id='{$productId}'";
    $row = db::get_one($sql);
    if (!$row['count'])
    {
        $skuDir='/uploads/product/sku/'.date('Y').'/'.date('m').'/'.date('d').'/';
        $shadeDir='/uploads/product/shade/'.date('Y').'/'.date('m').'/'.date('d').'/';
        $url='https://www.maccosmetics.com.cn';
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
    return $data;
};

$spider->start();