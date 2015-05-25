<?php
namespace Application\Controller;

use PAN\SimpleHtmlDom;

class ScraperController
{
    /**
     * @var string 
    */
    protected $json;

    /**
     * @param type $filename
     * @return float
     */
    public function getImageSizes($filename)
    {
        $headers = get_headers($filename, true);

        // Get image size in bytes
        $filesize = $headers["Content-Length"];

        // Convert to KB
        $convertFilesize = $filesize / 1024;

        // Return in two decimal places
        return number_format($convertFilesize, 2) . 'kb';
    }

    /**
     * @param type $price
     * @return mixed
     */
    function getUnitPrice($price)
    {
        // Extract numbers (float) from price (string)
        preg_match_all('!\d+\.*\d*!', $price ,$match);

        list($unitPrice) = $match[0];

        return $unitPrice;
    }

    /**
     * 
     * @param type $url
     * @return string
     */
    public function getCurl($url)
    {
        $ch = curl_init();
        
        $header = [
            'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg',
            'Connection: Keep-Alive',
            'Content-type: application/x-www-form-urlencoded;charset=UTF-8'
        ];
 
        $options = [
            CURLOPT_URL            => $url, 
            CURLOPT_HTTPHEADER     => $header,
            CURLOPT_USERAGENT      => "Mozilla/5.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)", //set user agent
            CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        ];

        curl_setopt_array($ch, $options);
        $scraped = curl_exec($ch);
        curl_close($ch);

        return $scraped;
    }

    /**
     * @param type $url
     * @return array
     */
    public function scrapeResult($url)
    {
        $page = $this->getCurl($url);

        // Create a DOM object
        $dom = new SimpleHtmlDom($url);
        $dom->load($page);
        
        // Create empty array to hold child nodes of seach product row
        $result = [];

        // Create array to wrap the result set
        $output['result'] = [];

        // Variable to calculate total unit prices
        $total = 0;

        // Search for product list on the page
        // We only need this section
        $productList = $dom->find('ul.productLister li');

        foreach ($productList as $product) {
            // Search and loop through each product
            foreach ($product->find('.product') as $item) {

                // Get product info for each occurence of product item
                // Find these child nodes (.productInner, .productInfoWrapper, .productInfo)
                $productInfo = $item->children(0)->children(0)->children(0)->find('h3', 0);

                // Get the unit price for each occurence of product item
                // Find these child nodes (.productInner.,pricingAndTrolleyOptions,
                // .pricing, .pricePerUnit)
                $unitPrices = $item->children(0)->children(1)->children(0)->find('p', 0);

                $result['title'] = $productInfo->plaintext;
                $result['size'] = $this->getImageSizes($productInfo->find('img', 0)->src);
                $result['unit_price'] = $this->getUnitPrice($unitPrices->plaintext);

                $total += $result['unit_price'];

                // Add each row into out wrapper
                array_push($output['result'], $result);
            }
        }
        
        // Add total of unit prices to the array wrapper
        $output['result']['total'] = $total;

        // Clear DOM
        $dom->clear(); 
        unset($dom);

        return $output;
    }

    /**
     * @param type $url
     * @return string
     */
    public function convertToJson($url)
    {
        // Replace newline characters with <br>
        $this->json = json_encode($this->scrapeResult($url));

        return $this->parse($this->json);
    }

    /**
     * 
     * @param type $string
     * @return string
     */
    public function parse($string) 
    {
        $search = [
            "\r\n",
            "\r",
            "\n",
            "\\r",
            "\\n",
            "\\r\\n"
        ];
        $replace = "\n";

        return str_replace($search, $replace, $string);
    }
}

?>
