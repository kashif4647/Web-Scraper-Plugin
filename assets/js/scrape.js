console.log(`Scrapping script Loaded!`);
jQuery.browser = {};
(function () {
    jQuery.browser.msie = false;
    jQuery.browser.version = 0;
    if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
        jQuery.browser.msie = true;
        jQuery.browser.version = RegExp.$1;
    }
})();
let cachedProductPages = {};

jQuery(document).on('click', '.scrape-cats-products', function() {
    let cat     = jQuery('option:selected', '.select-cats-drop select').attr('url');
    let cid     = jQuery('option:selected', '.select-cats-drop select').attr('cid');
    let page    = jQuery('.pagination').find(":selected").val();
    jQuery('.scraper-response').text('');

    if(page != undefined && page != '' && cachedProductPages[cid+'_'+page]){
        jQuery.blockUI({ message: 'Please wait...', overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
        let products = cachedProductPages[cid+'_'+page];
        generateProductsHTML(products, page);
    }else{
        if(!cat){
            jQuery.blockUI({ message: 'Please select any category.', overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
            jQuery.unblockUI({ fadeOut: 2000 });
            return false;
        }

        if(page != undefined && page != ''){
            cat = cat+'&page='+page;
        }else{
            page = 1;
            cat = cat+'&page='+page;
        }
    
    
        var source = new EventSource("/wp-content/plugins/product-scraper/logging/logging.php");
        source.onmessage = function(event) {
            if(!event.data) 
                msg = 'Started...';
            else
                msg = event.data;
            jQuery.blockUI({ message: msg, overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
        };
        
        jQuery.ajax({
            type: "POST",
            url: ajax_object.ajaxurl,
            data: {
                cat: cat,
                action: "scrapeCategoryProduct"
            },
            success: function(response){
                source.close();
                if(response){
                    cachedProductPages[cid+'_'+page] = response;
                    generateProductsHTML(response, page);
                }else{
                    jQuery.blockUI({ message: response.data, overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
                }
                jQuery.unblockUI({ fadeOut: 1000 });
            },
            error: function (jqXHR, exception) {
                source.close();
                var msg = '';
                if (jqXHR.status === 0) {
                    msg = 'Not connect.\n Verify Network.';
                } else if (jqXHR.status == 404) {
                    msg = 'Requested page not found. [404]';
                } else if (jqXHR.status == 500) {
                    msg = 'Internal Server Error [500].';
                } else if (exception === 'parsererror') {
                    msg = 'Requested JSON parse failed.';
                } else if (exception === 'timeout') {
                    msg = 'Time out error.';
                } else if (exception === 'abort') {
                    msg = 'Ajax request aborted.';
                } else {
                    msg = 'Uncaught Error.\n' + jqXHR.responseText;
                }
                jQuery('.scraper-response').text(msg);
                jQuery.blockUI({ message: msg, overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
                jQuery.unblockUI({ fadeOut: 3000 });
            },
        });
    }
});

function generateProductsHTML(response, pageCount){
    let products = response.data.products;
    let pagination = response.data.pagination;
    let html = ``;
    products.forEach(element => {
        let featured = '';
        if(element.featured.image)
            featured = `<img width="100" height="100" src="${element.featured.image}" class="attachment-thumbnail size-thumbnail">`;
        else
            featured = `<video width="100" height="100" src="${element.featured.video}" class="attachment-thumbnail size-thumbnail"></video>`;

        let score = null;
        if(element.reviews.score)
            score = element.reviews.score;
        else
            score = '';

        let total = null;
        if(element.reviews.total)
            total = element.reviews.total;
        else
            total = '';

        let tag = '';
        if(element.prod_tags.length > 0)
            tag = element.prod_tags[0];
        else
            tag = '';

        html += `
        <tr id="post-${element.pid}" class="iedit author-self level-0 post-${element.pid} type-page status-publish hentry">
            <th scope="row" class="check-column">
                <label class="screen-reader-text" for="cb-select-${element.pid}"> Select CodersClan Assessment </label>
                <input id="cb-select-${element.pid}" class="single-product" type="checkbox" name="post[]" value="${element.pid}">
                <div class="locked-indicator">
                    <span class="locked-indicator-icon" aria-hidden="true"></span> 
                </div>
            </th>
            <td class="thumb column-thumb" data-colname="Image">
                ${featured}
            </td>
            <td class="title column-title has-row-actions column-primary page-title" data-colname="Title">
                <span><a href="${element.url}" target="_blank">${element.title}</a></span>
            </td>
            <td class="price column-price" data-colname="price"><span>NT$ ${element.price.price}</span></td>
            <td class="category column-category" data-colname="category">
                <span>${element.prod_cats[0]}</span>
            </td>
            <td class="review column-review" data-colname="review">
                <span>${score} ${total}</span>
            </td>
            <td class="tags column-tags" data-colname="tags">
                <span>${tag}</span>
            </td>
            <td class="action column-action" data-colname="action">
                <input type="button" value="Publish" data-id="${element.pid}" class="button button-primary publish-me" />
            </td>
        </tr>
        `;
    });

    jQuery('#the-list').html(html);
    
    let page = ``;
    if(pagination.length){
        pagination.forEach(el => {
            page += `
            <option value="${el}">${el}</option>
            `;
        });
    
        jQuery('.pagination-main').css('display', 'block');
        jQuery('.pagination').html(page);
        jQuery('.pagination').val(pageCount);
    }else{
        jQuery('.pagination-main').css('display', 'none');
    }
    jQuery.unblockUI({ fadeOut: 1000 });
}

jQuery(document).on('change', '.pagination', function(){
    jQuery('.scrape-cats-products').click();
});

jQuery(document).on('click', '.action .publish-me', function(){
    let product_id = jQuery(this).attr('data-id');

    if(product_id){
        jQuery.blockUI({ message: 'Saving product(s) into database...', overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
    }
    jQuery('.scraper-response').text('');

    jQuery.ajax({
        type: "POST",
        url: ajax_object.ajaxurl,
        data: {
            product_id: product_id,
            action: "saveScrapedProducts"
        },
        success: function(response){
            if(response){
                jQuery.blockUI({ message: 'Product has been saved successfully!', overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
            }else{
                jQuery.blockUI({ message: response.data, overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
            }
            jQuery.unblockUI({ fadeOut: 1000 });
        },
        error: function (jqXHR, exception) {
            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connect.\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else if (exception === 'parsererror') {
                msg = 'Requested JSON parse failed.';
            } else if (exception === 'timeout') {
                msg = 'Time out error.';
            } else if (exception === 'abort') {
                msg = 'Ajax request aborted.';
            } else {
                msg = 'Uncaught Error.\n' + jqXHR.responseText;
            }
            jQuery('.scraper-response').text(msg);
            jQuery.blockUI({ message: msg, overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
            jQuery.unblockUI({ fadeOut: 3000 });
        },
    });

});

jQuery(document).on('click', '.publish-products', function(){
    let product_ids = [];
    jQuery('.single-product:checkbox:checked').each(function(i){
        product_ids[i] = jQuery(this).val();
    });

    //send ajax request
    if(product_ids.length){
        jQuery.blockUI({ message: 'Saving product(s) into database...', overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
    }
    jQuery('.scraper-response').text('');

    jQuery.ajax({
        type: "POST",
        url: ajax_object.ajaxurl,
        data: {
            product_ids: product_ids,
            action: "savePagedScrapedProducts"
        },
        success: function(response){
            if(response){
                jQuery.blockUI({ message: 'Product has been saved successfully!', overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
            }else{
                jQuery.blockUI({ message: response.data, overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
            }
            jQuery.unblockUI({ fadeOut: 1000 });
        },
        error: function (jqXHR, exception) {
            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connect.\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else if (exception === 'parsererror') {
                msg = 'Requested JSON parse failed.';
            } else if (exception === 'timeout') {
                msg = 'Time out error.';
            } else if (exception === 'abort') {
                msg = 'Ajax request aborted.';
            } else {
                msg = 'Uncaught Error.\n' + jqXHR.responseText;
            }
            jQuery('.scraper-response').text(msg);
            jQuery.blockUI({ message: msg, overlayCSS: { backgroundColor: '#00f' }, css: { padding: '5px', fontSize: '14px' } });
            jQuery.unblockUI({ fadeOut: 3000 });
        },
    });

});

jQuery(document).on('change', '#cb input', function() {
    if(jQuery(this).is(":checked")) {
        let check_len = jQuery('.single-product:checkbox:checked').length;

        if(check_len > 0){
            jQuery('.publish-product').css('display', 'block');
            jQuery('.publish-me').css('display', 'none');
        }
        
    }else{
        let check_len = jQuery('.single-product:checkbox:checked').length;
        if(check_len > 1){
            jQuery('.publish-product').css('display', 'block');
            jQuery('.publish-me').css('display', 'none');
        }else{
            jQuery('.publish-me').css('display', 'block');
            jQuery('.publish-product').css('display', 'none');
            jQuery('#cb input').val(jQuery(this).is(':checked'));
        }
    }
});

jQuery(document).on('change', 'input.single-product', function() {
    if(jQuery(this).is(":checked")) {
        let check_len = jQuery('.single-product:checkbox:checked').length;

        if(check_len > 1){
            jQuery('.publish-product').css('display', 'block');
            jQuery('.publish-me').css('display', 'none');
        }
        
    }else{
        let check_len = jQuery('.single-product:checkbox:checked').length;

        if(check_len > 1){
            jQuery('.publish-product').css('display', 'block');
            jQuery('.publish-me').css('display', 'none');
        }else{
            jQuery('.publish-me').css('display', 'block');
            jQuery('.publish-product').css('display', 'none');
            jQuery('#cb input').val(jQuery(this).is(':checked'));
        }
    }
});