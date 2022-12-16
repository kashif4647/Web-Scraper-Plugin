=== External Website Scraper ===
Tags: web scraper, web scraping, products scraper, external website scraper, web scraping plugin
Requires at least: 1.0
Tested up to: 6.0.1
Stable tag: 6.0.1
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 
##Description
This plugin scrapes products from external website (like https://www.pinkoi.com/) and automate the scraping process.

You can set time in the plugin settings page like 1 hour scrape 5 products etc.

Plugin also maintains the logs and also displays in the plugin settings page.

###Dependencies
This plugin depends on two external JavaScript libraries:
**PhantomJS**
    Linux:
    It is manually configured but if you have VPS server or NPM installed on your server then you can install it using NPM.
    Window:
    Please download PhantomJS lib for windows and add environment variables.
**CasperJS**
    Linux:
    It is manually configured but if you have VPS server or NPM installed on your server then you can install it using NPM.
    Window:
    Please download PhantomJS lib for windows and add environment variables.
**ACF Plugin**
    You need to install ACF Plugin and import the JSON file (you can get this from logging folder) `acf-products.json` using ACF tools option.

##Installation
    Import plugin file, install and activate as you install other plugins. After installtion, you will get Product Scraper tab in the left menu bar.
