var fs = require('fs');
var casper = require('casper').create({
    verbose: false,
    logLevel: "debug",
    pageSettings: {
        loadImages: false,
        loadPlugins: false,
        javascriptEnabled: true,
        customHeaders: {
            acceptEncoding: "gzip, deflate, br",
            acceptLanguage: "en-US, en; q=0.9, ar;q=0.8,fr;q=0.7",
            // userAgent: "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36 Edge/12.0"
            userAgent: "Mozilla/5.0 (Macintosh; U; Interl Mac OS X; en-US) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36 Edge/12.0"
        }
    }
});

var url = casper.cli.get('url');
var root = casper.cli.get('root');
var website = casper.cli.get('website');

var links;

function getLinks() {
    var links = document.querySelectorAll('.g-react-items-wrapper article.m-card-product .product-link a.link-bottom');
    return Array.prototype.map.call(links, function (e) {
        return e.getAttribute('href');
    });
}

function getPagination() {
    var page = document.querySelectorAll('li.m-react-pagination-selector-area .m-react-select.s-web-native option');
    return Array.prototype.map.call(page, function (e) {
        return e.getAttribute('value');
    });
}

// Opens Category page
var i = -1;
jsonLogFile('Loading category page...');
casper.start(url);

jsonLogFile('Fetching product links...');
casper.wait(7000, function () {
    casper.then(function () {
        productHTML = this.evaluate(getLinks);
        jsonLogFile('Product links loaded and now getting all pages count...');
        this.wait(500, function () {
            pagination = this.evaluate(getPagination);
            jsonLogFile('This category has '+ pagination.length+ ' pages');
        
            res = {
                pagination: pagination,
                productHTML: productHTML
            }
            console.log(JSON.stringify(res));
        });

    });
});

function jsonLogFile(jsonStr){
    fs.write(root+'/wp-content/plugins/product-scraper/logging/logging.txt', jsonStr, 'w');
}

casper.run();