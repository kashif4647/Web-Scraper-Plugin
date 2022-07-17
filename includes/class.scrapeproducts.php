<?php

class ProductScraper
{
	public function __construct()
	{
		add_action( 'admin_menu', array( $this, 'AdminSettingsPage' ) );
		add_action( 'admin_init', array( $this, 'scraper_settings_init') );
	}

	function scrapeCategories(){
		$json = SC_PLUGIN_DIR . 'logging/categories.json';
	
		$string = file_get_contents($json);
		if ($string === false) {
			echo "Something went wrong while fetching categories! Please try again.";
		}
		
		$allCategories = json_decode($string, true);
		if ($allCategories === null) {
			echo "Something went wrong while fetching categories! Please try again.";
		}

		echo "<select>";
			echo '<option url="">---Select category group---</option>';
		foreach ($allCategories['categories'] as $key => $category) {
			echo '<option url="'.$category['url'].'" cid="'.$category['cid'].'" style="font-weight: bold;">'.$category['name'].'</option>';
			
			$check = term_exists( $category['name'], 'category' );
			
			if(empty($check)){
				//create the main category
				$res = wp_insert_term(
					$category['name'],
					'category',
				);
			}else{
				$res = $check;
			}

			if(!empty($category['sub-cats'])){
				foreach ($category['sub-cats'] as $skey => $sub_category) {
					$check_sub = term_exists( $sub_category['name'], 'category' );
					
					if(empty($check_sub) && $res['term_id']){
						$resp = wp_insert_term(
							$sub_category['name'], // the term 
							'category', // the taxonomy
							array(
								'parent'=> $res['term_id']
							)
						);
					}
				
					echo '<option url="'.$sub_category['url'].'" cid="'.$sub_category['cid'].'">--- '.$sub_category['name'].'</option>';
				}
			}
		}
		echo "</select>";

	}

	/***********************************************/
	/************Registering Plugin Page***********/
	function AdminSettingsPage()
	{
		add_menu_page(
			'Product Scraper',     // page title
			'Product Scraper',     // menu title
			'manage_options',   // capability
			'product-scraper',     // menu slug
			array($this, 'ScraperPageContent')     // callback function
		);
		add_submenu_page( 
			'product-scraper', 
			'Shortcodes', 
			'Shortcodes',
			'manage_options', 
			'shortcodes',
			array($this, 'shortcodesCallback')
		);
		add_submenu_page(
			'product-scraper',
			'Settings',
			'Settings',
			'manage_options',
			'scraper-settings',
			array($this, 'scraperSettingsCallback')
		);
	}

	
	function shortcodesCallback(){
		require_once( SC_PLUGIN_DIR . 'templates/shortcodes.html' );
	}
	
	function scraperSettingsCallback(){
		require_once( SC_PLUGIN_DIR . 'templates/scrapeAutomatic.php' );
	}
	
	function ScraperPageContent()
	{
		require_once( SC_PLUGIN_DIR . 'templates/settings.php' );

		echo '<span class="scraper-response"></span>';
		echo '<div class="select-cats-drop">';
			$this->scrapeCategories();
			echo '<button class="button button-primary scrape-cats-products">Scrape Products</button>';
		echo '</div>';
		?>

		<div class="product-container">
			<div class="publish-product">
				<button class="button button-primary publish-products">Publish Products</button>
			</div>
			<div class="pagination-main">
				<span>Select page</span>
				<select class="pagination"></select>
			</div>
			<table class="wp-list-table widefat fixed striped table-view-list pages">
				<thead>
					<tr>
						<td id="cb" class="manage-column column-cb check-column">
							<label class="screen-reader-text" for="cb-select-all-1">Select All</label>
							<input id="cb-select-all-1" type="checkbox">
						</td>
						<th scope="col" id="thumb" class="manage-column column-thumb"><span class="wc-image tips">Image</span></th>
						<th scope="col" id="title" class="manage-column column-title column-primary"><span>Product Title</span></th>
						<th scope="col" id="price" class="manage-column column-price">Price</th>
						<th scope="col" id="category" class="manage-column column-category num"><span>Product Category</span></th>
						<th scope="col" id="review" class="manage-column column-review"><span>Reviews</span></th>
						<th scope="col" id="tags" class="manage-column column-tags"><span>Tags</span></th>
						<th scope="col" id="action" class="manage-column column-action"><span>Action</span></th>
					</tr>
				</thead>
				<tbody id="the-list">
					<tr><td colspan="8" style="text-align: center">No product found!</td></tr>
				</tbody>
			</table>
			<div class="pagination-main">
				<span>Select page</span>
				<select class="pagination"></select>
			</div>
		</div>

	<?php
	}

	function scraper_settings_init() {
		// Register a new setting for "scraper" page.
		register_setting( 'scraper-settings', 'hours_options' );
		register_setting( 'scraper-settings', 'quantity_options' );
	 
		// Register a new section in the "scraper" page.
		add_settings_section(
			'scraper_section_developers',
			__( 'Please fill the desired fields below.', 'scraper-hours' ), 
			array($this, 'scraper_section_developers_callback'),
			'scraper-hours'
		);
		
		// Register a new section in the "scraper" page.
		add_settings_section(
			'scraper_section_quantity',
			__( '', 'scraper-quantity' ), 
			array($this, 'scraper_section_developers_callback'),
			'scraper-quantity'
		);
	 
		// Register a new field in the "scraper_section_developers" section, inside the "scraper" page.
		add_settings_field(
			'scraper-field-hours', // As of WP 4.6 this value is used only internally.
									// Use $args' label_for to populate the id inside the callback.
				__( 'Hour(s)', 'scraper-hours' ),
			array($this, 'scraperFieldHours'),
			'scraper-hours',
			'scraper_section_developers',
			array(
				'label_for'         => 'scraper-field-hours',
				'class'             => 'scraper_row',
				'scraper_custom_data' => 'custom',
			)
		);
		
		add_settings_field(
			'scraper-field-quantity', // As of WP 4.6 this value is used only internally.
									// Use $args' label_for to populate the id inside the callback.
				__( 'Quantity', 'scraper-quantity' ),
			array($this, 'scraperFieldQuantity'),
			'scraper-quantity',
			'scraper_section_quantity',
			array(
				'label_for'         => 'scraper-field-quantity',
				'class'             => 'scraper_row',
				'scraper_custom_data' => 'custom',
			)
		);
	}

	function scraper_section_developers_callback(){}

	function scraperFieldHours( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$options = get_option( 'hours_options' );
		?>
		<input type="text" value="<?= $options['scraper-field-hours'] ?>" id="<?php echo esc_attr( $args['label_for'] ); ?>" class="scraper-settings" name="hours_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
		<?php
	}
	
	function scraperFieldQuantity( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$quantity = get_option( 'quantity_options' );
		?>
		<input type="text" value="<?= $quantity['scraper-field-quantity'] ?>" id="<?php echo esc_attr( $args['label_for'] ); ?>" class="scraper-settings" name="quantity_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
	<?php
	}
}
new ProductScraper();