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
    //'multiserver' => true,
    'log_show' => true,
    //'save_running_state' => false,
    'domains' => array(
        'www.esteelauder.com.cn'
    ),
    'scan_urls' => array(
        "https://www.esteelauder.com.cn/products/687/product-catalog",//唇膏
    ),
    'list_url_regexes' => array(
       "https://www.esteelauder.com.cn/products/687/product-catalog",//唇膏
    ),///29363/product-catalog/micro-essence
    'content_url_regexes' => array(
        "/product/687/[0-9a-zA-Z\_\-\/]+",
        //"/product/13853/\d+/[0-9a-zA-Z\.\_\-\/\#]+",
    ),
    //'export' => array(
    //'type' => 'db',
    //'table' => 'meinv_content',
    //),
    'db_config' => array(

    ),
    'fields' => array(
        // product
        array(
            'name' => "product_id",
            'selector' => "@window.PRODUCT_ID = \"(.*?)\";</script>@",
            'selector_type' => 'regex',
            'required' => true,
        ),
        array(
            'name' => "product_name_en",
            'selector' => ".product-full__title",
            'selector_type' => 'css',
            //'required' => true,
        ),
        array(
            'name' => "product_name",
            'selector' => ".product-full__subtitle",
            'selector_type' => 'css',
            'required' => true,
        ),
        array(
            'name' => "product_cover",
            'selector' => ".product-full__image > img",
            'selector_type' => 'css',
            'required' => true,
        ),
        array(
            'name' => "product_detail",
            'selector' => ".spp-product__details-description",
            'selector_type' => 'css',
            //'required' => true,
        ),
        array(
            'name' => "product_url",
            'selector' => ".spp-product__mini-bag-description",
            'selector_type' => 'css',
            'required' => true,
        ),
        array(
            'name' => "product_attr",
            'selector' => ".spp-product__mini-bag-image-container > p",
            'selector_type' => 'css',
            //'required' => true,
            'repeated'=>true
        ),
        array(
            'name' => "product_size",
            'selector' => ".product-full__price-text",
            'selector_type' => 'css',
            //'required' => true,
            //'repeated'=>true
        ),
        array(
            'name' => "product_price",
            'selector' => ".product-full__price-text > .product-full__price",
            'selector_type' => 'css',
            //'required' => true,
            //'repeated'=>true
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
    db::set_connect('default', $db_config);
    db::_init();
};

$spider->on_extract_field = function($fieldname, $data, $page)
{
    if ($fieldname == 'product_attr')
    {
        $product_use=@$data['0'];
        $product_effect=@$data['1'];
        $product_skin_type=@$data['2'];
        $product_counter=@$data['3'];
        $product=[
            'product_use'=>$product_use,
            'product_effect'=>$product_effect,
            'product_skin_type'=>$product_skin_type,
            'product_counter'=>$product_counter,
        ];
        $data=$product;
    }elseif ($fieldname=='product_url'){
        $reg1="/<a href=\"(.*?)\">(.*?)/i";
        preg_match_all($reg1,$data,$aarray);
        $data=empty($aarray['1']['0']) ? '':$aarray['1']['0'];
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
    $product=$data;
    //$product=empty($data['product_name_en']) ? []:$data['product_name_en'];
    /**if (!isset($category[trim($product['category'])]))
    {
        return false;
    }else{
        $categoryId=$category[trim($product['category'])];
    }*/
    $categoryId=1;
    $productId=trim($product['product_id']);
    $sql = "Select Count(*) As `count` From `t_estee_lauder` Where product_id='{$productId}'";
    $row = db::get_one($sql);
    if (!$row['count'])
    {
        $skuDir='/uploads/product/sku/'.date('Y').'/'.date('m').'/'.date('d').'/';
        $shadeDir='/uploads/product/shade/'.date('Y').'/'.date('m').'/'.date('d').'/';
        $url='https://www.esteelauder.com.cn';
        $rating=0;
        $skuDefalut=0;
        $product_size_arr=explode(' ', $product['product_size']);
        if(count($product_size_arr)>1){
            $product_unit=empty($product_size_arr[1]) ?'':$product_size_arr[1];
            $product_size=empty($product_size_arr[0]) ?'':$product_size_arr[0];
            if( preg_match('/\\d+/',$product_size,$matchs1) == 1){
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
            $product_unit=strtolower($product_unit);
            if(in_array($product_unit,  ['ml','毫升','g','克','mg','毫克','片','只','支'])){
                
            }else{
                $product_unit='';
                $product_size=0;
            }
        }
        $product_price=trim(str_replace('¥', '', $product['product_price']));
        $currency=1;
        $productData=[
            'product_id'=>$productId,
            'product_name'=>empty($product['product_name']) ?'':$product['product_name'],
            'product_name_en'=>empty($product['product_name_en'])?'':$product['product_name_en'],
            'product_cover'=>image::saveFile($skuDir,$url.$product['product_cover']),
            'product_feature'=>'',//json_encode($product['product_desc'],JSON_UNESCAPED_UNICODE),
            'buzzword'=>'',//$product['buzzword'],
            'source'=>'esteelauder',
            'source_url'=>empty($product['product_url']) ?'':$product['product_url'],
            'avg_rating'=>$rating,
            'category_id'=>$categoryId,
            'product_detail'=>empty($product['product_detail'])?'':$product['product_detail'],
            'product_use'=>empty($product['product_use'])?'':$product['product_use'],
            'product_effect'=>empty($product['product_effect'])?'':$product['product_effect'],
            'product_skin_type'=>empty($product['product_skin_type'])?'':$product['product_skin_type'],
            'product_counter'=>empty($product['product_counter'])?'':$product['product_counter'],
            'size'=>trim($product_size),
            'unit'=>trim($product_unit),
            'price'=>trim($product_price),
            'currency'=>$currency,
        ];
        $p_id=db::insert("t_estee_lauder", $productData);
    }
    return $data;
};

$spider->start();