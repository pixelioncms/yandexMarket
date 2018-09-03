<?php

Yii::import('mod.shop.ShopModule');

class DefaultController extends Controller {

    public function actionIndex() {
        $xml = new YandexMarketXML;
        $xml->processRequest();
    }

}
