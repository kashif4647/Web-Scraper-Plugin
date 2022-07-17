<?php
$json = SC_PLUGIN_DIR . 'logging/categories.json';
require_once SC_PLUGIN_DIR.'includes/functions.php';
require_once SC_PLUGIN_DIR.'includes/simplehtmldom/simple_html_dom.php';
	
$string = file_get_contents($json);
if ($string === false) {
    echo "Something went wrong while fetching categories! Please try again.";
}

$allCategories = json_decode($string, true);
if ($allCategories === null) {
    echo "Something went wrong while fetching categories! Please try again.";
}

foreach ($allCategories['categories'] as $key => $category) {
    $url    = $category['url'].'&sortby=created&order=desc';
    $cid    = $category['cid'];
    $name   = $category['name'];

    scrapeProductsAutomatic($url, $name);
    
    if(!empty($category['sub-cats'])){
        foreach ($category['sub-cats'] as $skey => $sub_category) {
            $c_url  = $sub_category['url'].'&sortby=created&order=desc';
            $c_cid  = $sub_category['cid'];
            $c_name = $sub_category['name'];
            scrapeProductsAutomatic($c_url, $c_name);
        }
    }
}

function scrapeProductsAutomatic($url, $name){
    $quantityOption = get_option('quantity_options');
    $quantity = $quantityOption['scraper-field-quantity'];

    $links = '';
    $links .= exec('casperjs '.$_SERVER['DOCUMENT_ROOT'].'/wp-content/plugins/product-scraper/assets/js/ScrapeCategoryProducts.js --url="'.$url.'" --root="'.$_SERVER['DOCUMENT_ROOT'].'" --website=https://www.pinkoi.com', $output);
    
    $resp = json_decode($links, true);
    $pagination         = $resp['pagination'];
    $allProductLinks    = $resp['productHTML'];

    $allProducts = array();
    if(!empty($allProductLinks)){
        automaticLogs("\n ".$quantity.' Product links has been loaded for category '.$name.'!');
        
        foreach ($allProductLinks as $key => $link) {
            if( strpos(parse_url($link, PHP_URL_HOST), 'pinkoi.com') !== false ) {
                $url = $link;
            }else{
                $url = 'https://www.pinkoi.com'.$link;
            }
            
            $product = scrapCatProducts($url);

            if(!empty($product)){
                // writeProductJSONFile($product);
                saveAutomaticScrapedProducts($product);

                if((int)$key+1 == $quantity){
                    // automaticLogs("New products has been scraped and published successfully! \n");
                    writeProductLogFile('');
                    break;
                }
            }
        }
    }
}

function saveAutomaticScrapedProducts($product){
    $product_id = $product['pid'];
    if($product_id){
        $check = checkProductExist($product_id);

        if($check['status'] === false){
            writeProductJSONFile($product);
            $productObj = (object) $product;
            publishProduct($productObj);
            automaticLogs('Product Published :: '.$productObj->title);
        }else{
            $productObj = (object)$product;
            updateProduct($productObj, $check['product_id']);
            // automaticLogs('Already Exist :: '.$product->title);
        }
    }
}

function automaticLogs($message) { 
    if(is_array($message)) { 
        $message = json_encode($message); 
    } 
    $file = fopen(SC_PLUGIN_DIR."logging/automaticLog.log","a"); 
    echo fwrite($file, "\n" . date('Y-m-d h:i:s') . " :: " . $message); 
    fclose($file); 
}