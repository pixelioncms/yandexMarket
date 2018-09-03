<?php

Yii::import('mod.shop.models.ShopCategory');

/**
 * Exports products catalog to YML format.
 */
class YandexMarketXML
{

    /**
     * @var int Maximum loaded products per one query
     */
    public $limit = 2;

    /**
     * @var string Default currency
     */
    public $currencyIso = 'UAH';

    /**
     * @var string
     */
    public $cacheFileName = 'yandex.market.xml';

    /**
     * @var string
     */
    public $cacheDir = 'application.runtime';

    /**
     * @var int
     */
    public $cacheTimeout = 86400;

    /**
     * @var resource
     */
    private $fileHandler;

    /**
     * @var integer
     */
    private $_config;

    /**
     * Initialize component
     */
    public function __construct()
    {
        $this->_config = Yii::app()->settings->get('yandexMarket');
        $this->currencyIso = Yii::app()->currency->getMain()->iso;
    }

    /**
     * Display xml file
     */
    public function processRequest()
    {
        $cache = Yii::app()->cache;
        $check = $cache->get($this->cacheFileName);
        if ($check === false) {
            $this->createXmlFile();
            if (!YII_DEBUG)
                $cache->set($this->cacheFileName, true, $this->cacheTimeout);
        }
        header("content-type: text/xml");
        echo file_get_contents($this->getXmlFileFullPath());
        exit;
    }

    /**
     * Create and write xml to file
     */
    public function createXmlFile()
    {
        $filePath = $this->getXmlFileFullPath();
        $this->fileHandler = fopen($filePath, 'w');

        $this->write("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
        //$this->write("<!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">\n");
        $this->write('<yml_catalog date="' . date('Y-m-d H:i') . '">');
        $this->write('<shop>');
        $this->renderShopData();
        $this->renderCurrencies();
        $this->renderCategories();
        $this->loadProducts();
        $this->write('</shop>');
        $this->write('</yml_catalog>');

        fclose($this->fileHandler);
    }

    /**
     * Write shop info
     */
    public function renderShopData()
    {
        $this->write('<name>' . $this->_config->name . '</name>');
        $this->write('<company>' . $this->_config->company . '</company>');
        $this->write('<url>' . $this->_config->url . '</url>');
        $this->write('<platform>' . Yii::app()->name . '</platform>');
        $this->write('<version>' . Yii::app()->getVersion() . '</version>');
        $this->write('<email>info@pixelion.com.ua</email>');
    }

    /**
     * Write list of available currencies
     */
    public function renderCurrencies()
    {
        $this->write('<currencies>');
        $this->write('<currency id="' . $this->currencyIso . '" rate="1"/>');
        $this->write('</currencies>');
    }

    /**
     * Write categories to xm file
     */
    public function renderCategories()
    {
        $categories = ShopCategory::model()->excludeRoot()->findAll();
        $this->write('<categories>');
        foreach ($categories as $c) {
            $parentId = null;
            $parent = $c->parent(); //getparent()
            if ($parent && $parent->id != 1)
                $parentId = 'parentId="' . $parent->id . '"';
            $this->write('<category id="' . $c->id . '" ' . $parentId . '>' . CHtml::encode($c->name) . '</category>');
        }
        $this->write('</categories>');
    }

    /**
     * Write offers to xml file
     */
    public function loadProducts()
    {
        $limit = $this->limit;
        $total = ceil(ShopProduct::model()->published()->count() / $limit);
        $offset = 0;

        $this->write('<offers>');

        for ($i = 0; $i <= $total; ++$i) {
            $products = ShopProduct::model()->published()->findAll(array(
                'limit' => $limit,
                'offset' => $offset,
            ));
            $this->renderProducts($products);

            $offset += $limit;
        }

        $this->write('</offers>');
    }

    /**
     * @param array $products
     */
    public function renderProducts(array $products)
    {

        foreach ($products as $p) {

            if (!count($p->variants)) {
                $data['url'] = $p->getAbsoluteUrl();
                $data['price'] = Yii::app()->currency->convert($p->price, $this->_config->currency_id);
                $data['currencyId'] = $this->currencyIso;
                $data['categoryId'] = ($p->mainCategory) ? $p->mainCategory->id : false;
                $data['picture'] = $p->getMainImageUrl('100x100') ? Yii::app()->createAbsoluteUrl($p->getMainImageUrl('100x100')) : null;
                $data['name'] = CHtml::encode($p->name);
                $data['vendor'] = ($p->manufacturer) ? $p->manufacturer->name : false;
                if(!empty($p->short_description)){
                    $data['description'] = $this->clearText($p->short_description);
                }

                $attribute = new CAttributes($p);
                $test = $attribute->getData();

                foreach($test as $a){
                    $data['param'][$a->name] = $a->value;
                }
            } else {
                foreach ($p->variants as $v) {
                    $name = strtr('{product}({attr} {option})', array(
                        '{product}' => $p->name,
                        '{attr}' => $v->attribute->title,
                        '{option}' => $v->option->value
                    ));

                    $hashtag = '#' . $v->attribute->name . ':' . $v->option->id;

                    $data = array(
                        'url' => $p->getAbsoluteUrl() . $hashtag,
                        'price' => Yii::app()->currency->convert(ShopProduct::calculatePrices($p, $p->variants, 0), $this->_config->currency_id),
                        'currencyId' => $this->currencyIso,
                        'categoryId' => ($p->mainCategory) ? $p->mainCategory->id : false,
                        'picture' => $p->attachmentsMain ? Yii::app()->createAbsoluteUrl($p->getMainImageUrl('100x100')) : null,
                        'name' => CHtml::encode($name),
                        'vendor' => CHtml::encode($p->manufacturer->name),
                        'description' => $this->clearText($p->short_description),
                    );
                }
            }
            $this->renderOffer($p, $data);
        }
    }

    /**
     * @param ShopProduct $p
     * @param array $data
     */
    public function renderOffer(ShopProduct $p, array $data)
    {
        $available = ($p->availability == 1) ? 'true' : 'false';
        $this->write('<offer id="' . $p->id . '" available="' . $available . '">');

        foreach ($data as $key => $val) {
            if(is_array($val)){
                foreach($val as $name=>$value){
                    $this->write("<param name=\"".$name."\">" . $value . "</param>\n");
                }
            }else{
                $this->write("<$key>" . $val . "</$key>\n");
            }
        }
        $this->write('</offer>' . "\n");
    }

    /**
     * @param $text
     * @return string
     */
    public function clearText($text)
    {
        return '<![CDATA['  . $text  . ']]>';
    }

    /**
     * @return string
     */
    public function getXmlFileFullPath()
    {
        return Yii::getPathOfAlias($this->cacheDir) . DS . $this->cacheFileName;
    }

    /**
     * Write part of xml to file
     * @param $string
     */
    private function write($string)
    {
        fwrite($this->fileHandler, $string);
    }

}
