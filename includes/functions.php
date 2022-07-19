<?php
add_action( "wp_ajax_scrapeCategoryProduct", "scrapeCategoryProduct" );
add_action( "wp_ajax_nopriv_scrapeCategoryProduct", "scrapeCategoryProduct" );
function scrapeCategoryProduct(){

    if(isset($_POST['cat']) && $_POST['cat'] != ''){
        is_connected();
        require_once SC_PLUGIN_DIR.'includes/simplehtmldom/simple_html_dom.php';

        // $url = getRedirectedURL($_POST['cat']);
        $url = $_POST['cat'];

        writeProductLogFile('Now sending request to external website');
        $links = '';
        $links .= exec('casperjs '.$_SERVER['DOCUMENT_ROOT'].'/wp-content/plugins/product-scraper/assets/js/ScrapeCategoryProducts.js --url="'.$url.'" --root="'.$_SERVER['DOCUMENT_ROOT'].'" --website=https://www.pinkoi.com', $output);
        
        $resp = json_decode($links, true);
        $pagination         = $resp['pagination'];
        $allProductLinks    = $resp['productHTML'];

        $allProducts = array();
        if(!empty($allProductLinks)){
            writeProductLogFile(count($allProductLinks).' Product links has been loaded!');
            
            foreach ($allProductLinks as $key => $link) {
                if( strpos(parse_url($link, PHP_URL_HOST), 'pinkoi.com') !== false ) {
                    $url = $link;
                }else{
                    $url = 'https://www.pinkoi.com'.$link;
                }
                
                $product = scrapCatProducts($url);
    
                if(!empty($product)){
                    writeProductJSONFile($product);
                    $allProducts[$key] = $product;

                    if(count($allProductLinks) -1 == (int)$key){
                        parse_str(parse_url($_POST['cat'])['query'], $params);
                        $page_no = $params['page'];
                        writeProductLogFile('All products has been scraped successfully for page '.$page_no.'!');
                    }else{
                        writeProductLogFile(($key+1).' of '.count($allProductLinks).' Product(s) has been scraped successfully!');
                    }
                }
            }
            sleep(1);
            writeProductLogFile('');
            wp_send_json_success(array( 'products' => $allProducts, 'pagination' => $pagination ));
        }else{
            writeProductLogFile('No Product Link Found!');
            wp_send_json_error('No Product Link Found!');
            sleep(1);
            writeProductLogFile('');
        }
    }else{
        writeProductLogFile('Please select any category.');
        wp_send_json_error('Please select any category.');
        sleep(1);
        writeProductLogFile('');
    }
    
    die();
}

function getRedirectedURL($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Must be set to true so that PHP follows any "Location:" header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $a = curl_exec($ch); // $a will contain all headers

    $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // This is what you need, it will return you the last effective URL
    return $url;
}

function scrapCatProducts($url){
    require_once SC_PLUGIN_DIR.'includes/simplehtmldom/simple_html_dom.php';
    
    $pid            = strtok(basename($url), '?');
    $html           = file_get_html($url);
    $title          = $html->find('.m-product-main-info.m-box.test-product-main-info h1', 0);
    
    if(!$title){
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $collect_data = array();
        $strCookie = 'adult_confirmed=1; path=/';
        curl_setopt( $curl, CURLOPT_COOKIE, $strCookie ); 
        $page = curl_exec($curl);
        $html = str_get_html($page);
    }
    
    $title          = $html->find('.m-product-main-info.m-box.test-product-main-info h1', 0);
    $symbol         = $html->find('.price.js-price span.symbol', 0);
    $price          = $html->find('.m-product-main-info.m-box.test-product-main-info .price.js-price span.amount', 0);
    $price_discount = $html->find('span.discount span.amount', 0);
    $images         = $html->find('.photos-thumbs .photos-thumbs__list .thumb-item.js-thumb-item img');
    $description    = $html->find('#description .m-richtext.js-detail-content-inner div[data-translate=description]', 0);
    $p_information  = $html->find('.m-product-basic-info.m-box.m-box-main .m-box-body dl.m-product-list', 0);
    $promotion      = $html->find('.m-product-promo-block.m-box.js-block-promo-box .m-box-body', 0);
    $brand          = $html->find('.shop_info .shop-name span.text-link', 0);
    $payment_method = $html->find('dd.m-product-list-content.payment-method', 0);

    $product_info   = array();
    $galleryImgs    = array();
    $featured       = array();
    $reviews        = array();
    $prod_tags      = array();
    $prod_cats      = array();

    if(!empty($html->find('.info-review'))){
        writeProductLogFile("Fetching product reviews...");
        foreach ($html->find('.info-review') as $review) {
            $reviews['score'] = $review->find('span.info-review__score', 0)->innertext;
            $reviews['total'] = $review->find('span.info-review__total', 0)->innertext;
        }
    }

    if(!empty($images)){
        writeProductLogFile("Fetching product gallery...");
        foreach($images as $key => $image) {
            $galleryImgs[$key] = str_replace("80x80", "800x0", $image->src);
        }
    }
    
    if(!empty($html->find('.m-product-list-item.tags dd.m-product-list-content .m-tag'))){
        writeProductLogFile("Fetching product tags...");
        foreach ($html->find('.m-product-list-item.tags dd.m-product-list-content .m-tag') as $key => $tag) {
            $prod_tags[$key] = $tag->innertext;
        }
    }
    
    if(!empty($html->find('.g-breadcrumb-v2 a'))){
        writeProductLogFile("Fetching product categories...");
        foreach ($html->find('.g-breadcrumb-v2 a') as $key => $cat) {
            $prod_cats[$key] = $cat->innertext;
        }
    }
    
    $re = '~h*(https?://?\S+?\.(?:jpe?g|gif|png|tiff|svg))~mx';
    $subst = '<img src="$1" />';
    $description_res = preg_replace($re, $subst, ($description) ? wp_kses_post($description->plaintext) : '');

    $product_info['url']            = ($url) ? $url : '';
    $product_info['pid']            = ($pid) ? $pid : '';
    $product_info['title']          = ($title) ? $title->plaintext : '';
    $product_info['reviews']        = $reviews;
    $product_info['price']          = array( 'symbol' => ($symbol) ? $symbol->plaintext : '', 'price' => ($price) ? $price->plaintext : '', 'discount' => ($price_discount)? $price_discount->plaintext : '' );
    $product_info['gallery']        = $galleryImgs;
    $product_info['product_info']   = ($p_information) ? wp_kses_post($p_information->plaintext) : '';
    $product_info['description']    = $description_res;
    $product_info['promotion']      = ($promotion) ? wp_kses_post($promotion->innertext) : '';
    $product_info['prod_tags']      = $prod_tags;
    $product_info['prod_cats']      = $prod_cats;
    $product_info['brand']          = ($brand) ? wp_kses_post($brand->innertext) : '';
    $product_info['payment_method'] = ($payment_method) ? wp_kses_post($payment_method->plaintext) : '';
    
    writeProductLogFile("Fetching product featured image...");
    if ( $product_info['gallery'] && strpos($product_info['gallery'][0], 'video')  !== false ) { 
        $featured['image'] = $product_info['gallery'][0];
        $featured['video'] = str_replace("800x0.jpg", "1080x1080.mp4", $product_info['gallery'][0]);
    }else{
        $featured['image'] = str_replace("80x80", "800x0", ($product_info['gallery']) ? $product_info['gallery'][0] : '');
    }
    $product_info['featured'] = $featured;

    return $product_info;
}

function writeProductLogFile($msg){
    $myfile = fopen(SC_PLUGIN_DIR."logging/logging.txt", "w") or die("Unable to open file!");
    fwrite($myfile, $msg);
    fclose($myfile);
}

function writeProductJSONFile($product){
    $inp = file_get_contents(SC_PLUGIN_DIR."logging/products.json");
    $tempArray = json_decode($inp);
    if(!empty($tempArray)){
        $id = $product['pid'];
        $tempArray->$id = $product;
    }else{
        $tempArray = (object)[];
        $id = $product['pid'];
        $tempArray->$id = $product;
    }
    $jsonData = json_encode($tempArray);
    file_put_contents(SC_PLUGIN_DIR."logging/products.json", $jsonData);
}

function is_connected()
{
    $connected = @fsockopen("www.google.com", 80); //website, port  (try 80 or 443)
    if ($connected){
        $is_conn = true; //action when connected
        fclose($connected);
        writeProductLogFile('Connection established!');
    }else{
        $is_conn = false; //action in connection failure
        writeProductLogFile('Please check you connection!');
    }

    return $is_conn;
}

function getJSONProducts($product_id){
    $inp        = file_get_contents(SC_PLUGIN_DIR."logging/products.json");
    $products   = json_decode($inp);
    $product    = $products->$product_id;

    return $product;
}

function getCatIdsByName($names){
    $ids = [];
    foreach ($names as $key => $name) {
		$check = term_exists( $name, 'category' );
        
        if(!empty($check)){
            array_push($ids, $check['term_id']);
        }
    }

    return $ids;
}

add_action( "wp_ajax_savePagedScrapedProducts", "savePagedScrapedProducts" );
add_action( "wp_ajax_nopriv_savePagedScrapedProducts", "savePagedScrapedProducts" );
function savePagedScrapedProducts(){
    $product_ids = $_POST['product_ids'];

    if(!empty($product_ids)){
        foreach ($product_ids as $key => $id) {
            $product = getJSONProducts($id);

            $check = checkProductExist($id);

            if($check['status'] === false){
                publishProduct($product);
            }else{
                updateProduct($product, $check['product_id']);
            }
        }
        wp_send_json_success('All product(s) has been saved successfully!');
    }else{
        wp_send_json_error('Please select any product to publish.');
    }
}

add_action( "wp_ajax_saveScrapedProducts", "saveScrapedProducts" );
add_action( "wp_ajax_nopriv_saveScrapedProducts", "saveScrapedProducts" );
function saveScrapedProducts(){
    $product_id = $_POST['product_id'];

    if($product_id){
        $product = getJSONProducts($product_id);
        $check = checkProductExist($product_id);

        if($check['status'] === false){
            publishProduct($product);
        }else{
            updateProduct($product, $check['product_id']);
        }
        
        wp_send_json_success('Product has been saved successfully!');
    }else{
        wp_send_json_error('Please provide product ID!');
    }
}

function setPostThumbnailFromURL($url, $post_id){
    // Add Featured Image to Post
    $image_name       = pathinfo($url);
    $image_name       = $image_name['basename'];
    $upload_dir       = wp_upload_dir(); // Set upload folder
    $image_data       = file_get_contents($url); // Get image data
    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
    $filename         = basename( $unique_file_name ); // Create image file name

    // Check folder permission and define file location
    if( wp_mkdir_p( $upload_dir['path'] ) ) {
    $file = $upload_dir['path'] . '/' . $filename;
    } else {
    $file = $upload_dir['basedir'] . '/' . $filename;
    }

    // Create the image  file on the server
    file_put_contents( $file, $image_data );

    // Check image file type
    $wp_filetype = wp_check_filetype( $filename, null );

    // Set attachment data
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name( $filename ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    // Create the attachment
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

    // Include image.php
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Define attachment metadata
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

    // Assign metadata to attachment
    wp_update_attachment_metadata( $attach_id, $attach_data );

    // And finally assign featured image to post
    set_post_thumbnail( $post_id, $attach_id );
}

function publishProduct($product){
    $catIds = getCatIdsByName($product->prod_cats);

    global $user_ID;
    $new_post = array(
        'post_title' => $product->title,
        'post_content' => wp_kses_post($product->description),
        'post_status' => 'publish',
        'post_date' => date('Y-m-d H:i:s'),
        'post_author' => $user_ID,
        'post_type' => 'post',
        'post_category' => $catIds
    );
    $post_id = wp_insert_post($new_post);

    if($post_id){
        if(isset($product->price->price) && $product->price->price){
            $price = $product->price->price;
        }else{
            $price = '';
        }
        wp_set_post_tags( $post_id, $product->prod_tags, true );
        update_post_meta($post_id, 'product_title', $product->title);
        update_post_meta($post_id, 'product_description', wp_kses_post($product->description));
        update_post_meta($post_id, 'sku', $product->pid);
        update_post_meta($post_id, 'price', 'NT$ '.$price);
        if(isset($product->price->discount) && trim($product->price->discount)){
            update_post_meta($post_id, 'discount_price', 'NT$ '.$product->price->discount);
        }
        update_post_meta($post_id, 'product_link', $product->url);
        update_post_meta($post_id, 'product_brand', trim(wp_filter_nohtml_kses($product->brand)));
        if( isset($product->reviews->score) && trim($product->reviews->score) ){
            update_post_meta($post_id, 'reviews', $product->reviews->score.' '.$product->reviews->total);
        }
        update_post_meta($post_id, 'product_information', wp_kses_post($product->product_info));
        update_post_meta($post_id, 'promotions', wp_kses_post($product->promotion));
        
        if(isset($product->payment_method) && trim($product->payment_method)){
            $payment_t = preg_split('/\s+/', $product->payment_method);
            $payment = implode(',', array_filter($payment_t));
            update_post_meta($post_id, 'payment_method', $payment);
        }
        
        if(isset($product->featured->image) && $product->featured->image){
            update_post_meta($post_id, 'featured_image', $product->featured->image);
            
            setPostThumbnailFromURL('https:'.$product->featured->image, $post_id);
        }
        if(isset($product->featured->video) && $product->featured->video){
            update_post_meta($post_id, 'featured_video', $product->featured->video);
        }

        // if( !empty($product->gallery) ){
        //     uploadGalleryImages($product->gallery, $post_id);
        // }
    }
}

function updateProduct($product, $product_id){
    $catIds = getCatIdsByName($product->prod_cats);

    global $user_ID;
    $new_post = array(
        'ID' => $product_id,
        'post_title' => $product->title,
        'post_content' => wp_kses_post($product->description),
        'post_status' => 'publish',
        'post_date' => date('Y-m-d H:i:s'),
        'post_author' => $user_ID,
        'post_type' => 'post',
        'post_category' => $catIds
    );
    $post_id = wp_update_post($new_post);

    if($post_id){
        if(isset($product->price->price) && $product->price->price){
            $price = $product->price->price;
        }else{
            $price = '';
        }
        wp_set_post_tags( $post_id, $product->prod_tags, true );
        update_post_meta($post_id, 'product_title', $product->title);
        update_post_meta($post_id, 'product_description', wp_kses_post($product->description));
        update_post_meta($post_id, 'sku', $product->pid);
        update_post_meta($post_id, 'price', 'NT$ '.$price);
        if(isset($product->price->discount) && trim($product->price->discount)){
            update_post_meta($post_id, 'discount_price', 'NT$ '.$product->price->discount);
        }
        update_post_meta($post_id, 'product_link', $product->url);
        update_post_meta($post_id, 'product_brand', trim(wp_filter_nohtml_kses($product->brand)));
        if( isset($product->reviews->score) && trim($product->reviews->score) ){
            update_post_meta($post_id, 'reviews', $product->reviews->score.' '.$product->reviews->total);
        }
        update_post_meta($post_id, 'product_information', wp_kses_post($product->product_info));
        update_post_meta($post_id, 'promotions', wp_kses_post($product->promotion));

        if(isset($product->payment_method) && trim($product->payment_method)){
            $payment_t = preg_split('/\s+/', $product->payment_method);
            $payment = implode(',', array_filter($payment_t));
            update_post_meta($post_id, 'payment_method', $payment);
        }

        if(isset($product->featured->image) && $product->featured->image){
            update_post_meta($post_id, 'featured_image', $product->featured->image);
            setPostThumbnailFromURL('https:'.$product->featured->image, $post_id);
        }
        if(isset($product->featured->video) && $product->featured->video){
            update_post_meta($post_id, 'featured_video', $product->featured->video);
        }

        // if( !empty($product->gallery) ){
        //     uploadGalleryImages($product->gallery, $post_id);
        // }
    }
}

function uploadGalleryImages($gallery, $post_id){
    $gallery_img = array();
    foreach($gallery as $image) {
        $gallery_img[] = media_sideload_image( 'https:'.$image, $post_id, NULL, 'id' );
    }
    update_post_meta( $post_id, 'gallery', $gallery_img );
}

function checkProductExist($product_sku){
    $args = array(
        'numberposts'	=> -1,
        'post_type'		=> 'post',
        'meta_key'		=> 'sku',
        'meta_value'	=> $product_sku
    );
    
    // query
    $the_query = new WP_Query( $args );

    if( $the_query->have_posts() ){
        while( $the_query->have_posts() ) : $the_query->the_post();
            return array(
                "status" => true,
                "product_id" => get_the_ID()
            );
        endwhile;
    }else{
        return array(
            "status" => false,
            "product_id" => NULL
        );
    }
}