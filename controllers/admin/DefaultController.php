<?php

class DefaultController extends AdminController {

    public $topButtons = false;

    public function actionIndex() {
        Yii::import('mod.shop.ShopModule');
        $this->pageName = Yii::t('YandexMarketModule.default', 'MODULE_NAME');

        $this->breadcrumbs = array(
            Yii::t('ShopModule.default', 'MODULE_NAME') => array('/admin/shop'),
            $this->pageName
        );

        $model = new SettingsYandexMarketForm;

        $this->topButtons = array(
            array('label' => Yii::t('app', 'RESET_SETTINGS'),
                'url' => $this->createUrl('resetSettings', array(
                    'model' => get_class($model),
                    'ref' => '/admin/yandexMarket'
                )),
                'htmlOptions' => array('class' => 'btn btn-outline-secondary')
            )
        );

        if (isset($_POST['SettingsYandexMarketForm'])) {
            $model->attributes = $_POST['SettingsYandexMarketForm'];
            if ($model->validate()) {
                $model->save();
                $this->refresh();
            }
        }
        $this->render('index', array('model' => $model));
    }

}
