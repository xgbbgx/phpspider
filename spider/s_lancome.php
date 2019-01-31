<?php
/**
 * 兰蔻
 */
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;
use phpspider\core\db;
use phpspider\core\image;

/* Do NOT delete this comment */
/* 不要删除这段注释 */
$configs = array(
    'name' => 'lancome',
    'tasknum' => 1,
    //'multiserver' => true,
    'log_show' => true,
    //'save_running_state' => false,
    'domains' => array(
        'www.lancome.com.cn'
    ),
    'scan_urls' => array(
        "https://www.lancome.com.cn/l3_axe_skincare_the_eye_care_and_lip_care",//唇膏
    ),
    'list_url_regexes' => array(
       "https://www.lancome.com.cn/l3_axe_skincare_the_eye_care_and_lip_care",//唇膏
    ),///29363/product-catalog/micro-essence
    'content_url_regexes' => array(
        "/item/[0-9a-zA-Z\_\-\/]+",
        //"/product/13853/\d+/[0-9a-zA-Z\.\_\-\/\#]+",
    ),
    //'export' => array(
    //'type' => 'db',
    //'table' => 'meinv_content',
    //),
    'db_config' => array(
        'host'  => '115.29.189.76',
        'port'  => 3306,
        'user'  => 'root',
        'pass'  => 'xgb3217144',
        'name'  => 'spider_dev',
        'charset'=>'utf8',
    ),
    'fields' => array(
        array(
            'name' => "product_id",
            'selector' => "@itemstyle=\"(.*?)\"@",
            'selector_type' => 'regex',
            'required' => true,
        ),
        array(
            'name' => "product_name_en",
            'selector' => ".product-tit > h2",
            'selector_type' => 'css',
            //'required' => true,
        ),
        array(
            'name' => "product_name",
            'selector' => ".product-tit > h1",
            'selector_type' => 'css',
            'required' => true,
        ),
        array(
            'name' => "product_cover",
            'selector' => ".events-master-scroll",
            'selector_type' => 'css',
            'required' => true,
        ),
        array(
            'name' => "product_detail",
            'selector' => ".product-introduction-box",
            'selector_type' => 'css',
            'required' => true,
        ),
        array(
            'name' => "product_desc",
            'selector' => ".tabs-container",
            'selector_type' => 'css',
           // 'required' => true,
        ),
        array(
            'name' => "product_price",
            'selector' => ".priceAndNum > .product-price",
            'selector_type' => 'css',
            //'required' => true,
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
    if ($fieldname == 'product_cover')
    {
        $productCover=[];
        $pattern="/<img(.*?)data-cloudzoom=\"(.*?)\"\/>/i";
        if (preg_match_all($pattern, $data,$arr)){
            $arrImg=empty($arr['2']) ? []:$arr['2'];
            foreach ($arrImg as $im){
                if (preg_match_all("/zoomImage: \'(.*?)\?/i", $im,$arr1)){
                    $productCover[]=empty($arr1['1']['0']) ?'':$arr1['1']['0'];
                }
            }
        }
        $data=$productCover;
    }else if ($fieldname == 'product_name'){
        $data=trim(str_replace('&#13;', '', strip_tags($data)));
    }else if ($fieldname == 'product_detail'){
        $data=str_replace('&#13;', '', strip_tags($data,'<div><br>'));
        $data=str_replace('产品简介', '',$data);
        $data=trim(str_replace('查看更多', '',$data));
    }else if ($fieldname == 'product_desc'){
        $data=str_replace('&#13;', '', strip_tags($data,'<div><br>'));
        $data=trim(str_replace('&gt;', '',str_replace('查看更多', '',$data)));
    }else if ($fieldname == 'product_price'){
        $data=trim(str_replace('¥', '', $data));
    }
    return $data;
};

$category = array(
    '唇膏' => '173'
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
    if(empty($productId)){
        return false;
    }
    $sql = "Select Count(*) As `count` From `t_lancome` Where product_id='{$productId}'";
    $row = db::get_one($sql);
    if (!$row['count'])
    {
        $skuDir='/uploads/product/'.date('Y').'/'.date('m').'/'.date('d').'/';
        $shadeDir='/uploads/product/'.date('Y').'/'.date('m').'/'.date('d').'/';
        $url='https://www.lancome.com.cn';
       
        $coverData=[];
        if($product['product_cover']){
            $cover=$product['product_cover'];
            foreach ($cover as $c){
                $coverData[]=image::saveFile($skuDir,$c);
            }
        }
        $rating=0;
        $skuDefalut=0;
        $productData=[
            'product_id'=>$productId,
            'product_name'=>empty($product['product_name']) ?'':$product['product_name'],
            'product_name_en'=>empty($product['product_name_en'])?'':$product['product_name_en'],
            'product_cover'=>json_encode($coverData),
            'product_feature'=>$product['product_desc'],
            'buzzword'=>'',//$product['buzzword'],
            'source'=>'lancome',
            'source_url'=>empty($product['product_url']) ?'':$product['product_url'],
            'avg_rating'=>$rating,
            'category_id'=>$categoryId,
            'product_detail'=>empty($product['product_detail'])?'':$product['product_detail'],
            'product_use'=>empty($product['product_use'])?'':$product['product_use'],
            'product_effect'=>empty($product['product_effect'])?'':$product['product_effect'],
            'product_skin_type'=>empty($product['product_skin_type'])?'':$product['product_skin_type'],
            'product_counter'=>empty($product['product_counter'])?'':$product['product_counter'],
            'price'=>empty($product['product_price'])?0:$product['product_price'],
            'currency'=>1,
        ];
        $p_id=db::insert("t_lancome", $productData);
    }
    return $data;
};

$spider->start();