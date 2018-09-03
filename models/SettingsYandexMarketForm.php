<?php

class SettingsYandexMarketForm extends FormSettingsModel {

    const MODULE_ID = 'yandexMarket';

    public $name;
    public $company;
    public $url;
    public $currency_id;

    public static function defaultSettings() {
        return array(
            'name' => Yii::app()->settings->get('app', 'site_name'),
            'company' => 'Демо кампания',
            'url' => Yii::app()->request->hostInfo,
            'currency_id' => null,
        );
    }

    public function getForm() {
        return new CMSForm(array(
            'attributes' => array(
                'id' => __CLASS__,
            ),
            'showErrorSummary' => true,
            'elements' => array(
                'name' => array(
                    'type' => 'text',
                    'hint' => self::t('HINT_NAME')
                ),
                'company' => array(
                    'type' => 'text',
                    'hint' => self::t('HINT_COMPANY')
                ),
                'url' => array(
                    'type' => 'text',
                    'hint' => self::t('HINT_URL')
                ),
                'currency_id' => array(
                    'type' => 'dropdownlist',
                    'items' => $this->getCurrencies(),
                    'empty' => Yii::t('app', 'EMPTY_LIST'),
                ),
            ),
            'buttons' => array(
                'submit' => array(
                    'type' => 'submit',
                    'class' => 'btn btn-success',
                    'label' => Yii::t('app', 'SAVE')
                ),
                'button' => array(
                    'type' => 'button',
                    'label' => Yii::t('YandexMarketModule.default', 'VIEW_FILE'),
                    'attributes' => array(
                        'onclick' => 'window.open("/yandex-market.xml","_blank");',
                        'class' => 'btn btn-default',
                    )
                )
            )
                ), $this);
    }

    public function validateCurrency() {
        $currencies = Yii::app()->currency->getCurrencies();
        if (count($currencies)) {
            if (!array_key_exists($this->currency_id, $currencies))
                $this->addError('currency_id', self::t('ERROR_CURRENCY'));
        }
    }

    public function rules() {
        return array(
            array('currency_id', 'validateCurrency'),
            array('name, company, url', 'type', 'type' => 'string'),
        );
    }

    public function getCurrencies() {
        $result = array();
        foreach (Yii::app()->currency->getCurrencies() as $id => $model)
            $result[$id] = $model->name;
        return $result;
    }

}
