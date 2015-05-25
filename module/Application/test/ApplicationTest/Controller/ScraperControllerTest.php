<?php

use Application\Controller\ScraperController;
//use PHPUnit_Framework_TestCase;

class ScraperControllerTest extends PHPUnit_Framework_TestCase
{
    protected $url;
    protected $scrapper;
    protected $page;

    public function setUp()
    {
        $this->url = "http://www.sainsburys.co.uk/webapp/wcs/stores/servlet/CategoryDisplay?listView=true&orderBy=FAVOURITES_FIRST&parent_category_rn=12518&top_category=12518&langId=44&beginIndex=0&pageSize=20&catalogId=10137&searchTerm=&categoryId=185749&listId=&storeId=10151&promotionId=#langId=44&storeId=10151&catalogId=10137&categoryId=185749&parent_category_rn=12518&top_category=12518&pageSize=20&orderBy=FAVOURITES_FIRST&searchTerm=&beginIndex=0&hideFilters=true";

        $this->scrapper = new ScraperController();
        $this->page = $this->scrapper->getCurl($this->url);
    }

    public function testGetCurlConvertPageToString()
    {        
        // Assertions
        $this->assertNotNull($this->page);
        $this->assertRegExp('/productLister/', $this->page);
    }

    public function testScrapeResultReturnsArray()
    {
        $output = $this->scrapper->scrapeResult($this->url);

        // Assertions
        $this->assertArrayHasKey('result', $output);
        $this->assertArrayHasKey('total', $output['result']);

        foreach ($output['result'] as  $product) {
           if (is_array($product)) {
                $this->assertArrayHasKey('title', $product);
                $this->assertArrayHasKey('size', $product);
                $this->assertArrayHasKey('unit_price', $product);
           }
        }
    }

    public function testConvertToJsonReturnJson()
    {
       $json = $this->scrapper->convertToJson($this->url);

       // Assertions
       $this->assertNotNull($json);
       $this->assertRegExp('/{.*?/', $json);
    }

    public function testGetImageSizesReturnsTheSize()
    {
        $imageUrl = "http://c2.sainsburys.co.uk/wcsstore7.07.1.143/SainsburysStorefrontAssetStore/wcassets/product_images/media_474184_M.jpg";
        
        $imageSize = $this->scrapper->getImageSizes($imageUrl);

        // Assertions
        $this->assertNotNull($imageSize);
        $this->assertRegExp('/kb/', $this->page);
    }

    public function testGetUnitPriceReturnsFloat()
    {
        $price    = '£2.50/unit';
        $expected = "2.50";

        $unitPrice = $this->scrapper->getUnitPrice($price);

        // Assertions
        $this->assertEquals($expected, $unitPrice);
        $this->assertNotEquals($price, $unitPrice);
    }
}

?>
