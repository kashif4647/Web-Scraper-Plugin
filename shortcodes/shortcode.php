<?php
function shortcodeProductTitle(){
    ob_start();

    global $post;

    echo do_shortcode('[acf field="product_title" post_id="'.$post->ID.'"]');

    return ob_get_clean();
}
add_shortcode('shortcodeProductTitle', 'shortcodeProductTitle');

function shortcodeProductLink(){
    ob_start();

    global $post;

    echo do_shortcode('[acf field="product_link" post_id="'.$post->ID.'"]');

    return ob_get_clean();
}
add_shortcode('shortcodeProductLink', 'shortcodeProductLink');

function shortcodeProductReviews(){
    ob_start();

    global $post;

    echo do_shortcode('[acf field="reviews" post_id="'.$post->ID.'"]');

    return ob_get_clean();
}
add_shortcode('shortcodeProductReviews', 'shortcodeProductReviews');

function shortcodeProductPrice(){
    ob_start();

    global $post;

    $price = get_field( "price", $post->ID );
    $discount_price = get_field( "discount_price", $post->ID );
    ?>
    <div class="price discount-price js-price">
        <span class="amount"><?= $price ?></span>
        <?php
        if($discount_price){ ?>
            <span class="discount">
                <span class="amount"><?= $discount_price ?></span>
            </span>
        <?php
        } ?>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('shortcodeProductPrice', 'shortcodeProductPrice');

function shortcodeProductCategories(){
    ob_start();

    global $post;

    $post_categories = wp_get_post_categories( $post->ID );
    $cats = '';
    
    foreach($post_categories as $c){
        $cat = get_category( $c );
        $cats .= '<a href="'.$cat->slug.'">'.$cat->name.'</a> > ';
    }

    echo $cats;
    return ob_get_clean();
}
add_shortcode('shortcodeProductCategories', 'shortcodeProductCategories');

function shortcodeProductFeatureImage(){
    ob_start();
    global $post;

    if (has_post_thumbnail( $post->ID ) ){
        $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' ); ?>
        
        <div class="image">
            <img src="<?= $image[0] ?>" />
        </div>
        <?php
    }else{ ?>
        <div class="image">
            <img src="<?= do_shortcode('[acf field="featured_image" post_id="'.$post->ID.'"]') ?>" />
        </div>
    <?php
    }
    
    return ob_get_clean();
}
add_shortcode('shortcodeProductFeatureImage', 'shortcodeProductFeatureImage');

function shortcodeProductPromotion(){
    ob_start();
    global $post;

    echo do_shortcode('[acf field="promotions" post_id="'.$post->ID.'"]');
    
    return ob_get_clean();
}
add_shortcode('shortcodeProductPromotion', 'shortcodeProductPromotion');

function shortcodeProductDescription(){
    ob_start();
    global $post;

    echo do_shortcode('[acf field="product_description" post_id="'.$post->ID.'"]');
    
    return ob_get_clean();
}
add_shortcode('shortcodeProductDescription', 'shortcodeProductDescription');

function shortcodeProductInformation(){
    ob_start();
    global $post;

    echo do_shortcode('[acf field="product_information" post_id="'.$post->ID.'"]');
    
    return ob_get_clean();
}
add_shortcode('shortcodeProductInformation', 'shortcodeProductInformation');

function shortcodeProductBrand(){
    ob_start();
    global $post;

    echo do_shortcode('[acf field="product_brand" post_id="'.$post->ID.'"]');
    
    return ob_get_clean();
}
add_shortcode('shortcodeProductBrand', 'shortcodeProductBrand');

function shortcodeProductPaymentMethods(){
    ob_start();
    global $post;

    echo do_shortcode('[acf field="payment_method" post_id="'.$post->ID.'"]');
    
    return ob_get_clean();
}
add_shortcode('shortcodeProductPaymentMethods', 'shortcodeProductPaymentMethods');

function shortcodeProductTags(){
    ob_start();
    global $post;

    $posttags = get_the_tags($post->ID);
    // echo "<p>TEST</p>";
    $tags = '';
    if ($posttags) {
        foreach($posttags as $tag) {
            $tags .= $tag->name . ', '; 
        }
    }

    echo $tags;
    
    return ob_get_clean();
}
add_shortcode('shortcodeProductTags', 'shortcodeProductTags');