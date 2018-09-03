<?php

/**
 * Модуль импорта товаров в ЯндексМаркет
 *
 * @author PIXELION CMS development team <info@pixelion.com.ua>
 * @link http://pixelion.com.ua PIXELION CMS
 * @package modules
 * @subpackage commerce.yandexmarket
 * @uses WebModule
 */
class YandexMarketModule extends WebModule {

    public function init() {
        $this->setImport(array(
            $this->id . '.models.*',
            $this->id . '.components.*',
        ));
        $this->setIcon('icon-yandex');
    }

    /**
     * Установка модуля
     * @return boolean
     */
    public function afterInstall() {
        if (Yii::app()->hasModule('shop')) {
            if (Yii::app()->hasComponent('settings'))
                Yii::app()->settings->set('yandexMarket', SettingsYandexMarketForm::defaultSettings());
            return parent::afterInstall();
        } else {
            Yii::app()->controller->setNotify('Ошибка, Модуль интернет-магазин не устрановлен.', 'error');
            return false;
        }
    }

    /**
     * Удаление модуля
     * @return boolean
     */
    public function afterUninstall() {
        Yii::app()->settings->clear('yandexMarket');
        return parent::afterUninstall();
    }

    public function getRules() {
        return array(
            '/yandex-market.xml' => '/yandexMarket/default/index',
        );
    }

    public function getAdminMenu() {
        return array(
            'shop' => array(
                'items' => array(
                    array(
                        'label' => $this->name,
                        'url' => $this->adminHomeUrl,
                        'active' => $this->getIsActive('yandexMarket/default'),
                        'icon' => Html::icon($this->icon),
                        'visible' => Yii::app()->user->openAccess(array('YandexMarket.Default.*','YandexMarket.Default.Index')),
                    ),
                ),
            ),
        );
    }

    public function getAdminSidebarMenu() {
        Yii::import('mod.admin.widgets.EngineMainMenu');
        $mod = new EngineMainMenu;
        $items = $mod->findMenu('shop');
        return $items['items'];
    }

}
