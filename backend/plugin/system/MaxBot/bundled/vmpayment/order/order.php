<?php

defined('_JEXEC') or die;

class plgVmPaymentOrder extends vmPSPlugin
{
    private const DEFAULT_FIELDS = 'order_number,order_created,order_name,phone_1,delivery_time,address_1,ulica,podyezd,kvartira,items,total';

    public function sendDataToServer($data, $url)
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return;
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_exec($ch);
        curl_close($ch);
    }

    public function plgVmConfirmedOrder($cart, $order)
    {
        $timeMap = array(
            'R' => '11:00',
            'A' => '12:00',
            'B' => '13:00',
            'C' => '14:00',
            'D' => '15:00',
            'E' => '16:00',
            'F' => '17:00',
            'G' => '18:00',
            'H' => '19:00',
            'I' => '20:00',
            'J' => '21:00',
        );

        $bt = isset($order['details']['BT']) ? $order['details']['BT'] : null;
        if (!is_object($bt)) {
            return true;
        }

        $usageType = isset($bt->tax_usage_type) ? (string) $bt->tax_usage_type : '';
        $utime = isset($timeMap[$usageType]) ? $timeMap[$usageType] : '-';

        $enabled = $this->getEnabledFields();
        $lines = array();

        $fieldMap = array(
            'order_number' => array('Номер заказа', $this->getBtValue($bt, 'order_number')),
            'order_created' => array('Дата и время заказа', $this->getBtValue($bt, 'order_created')),
            'order_name' => array('Имя', $this->getBtValue($bt, 'order_name')),
            'phone_1' => array('Телефон', $this->getBtValue($bt, 'phone_1')),
            'delivery_time' => array('Время доставки', $utime),
            'address_1' => array('Город', $this->getBtValue($bt, 'address_1')),
            'ulica' => array('Улица', $this->getBtValue($bt, 'ulica')),
            'podyezd' => array('Подъезд', $this->getBtValue($bt, 'podyezd')),
            'kvartira' => array('Квартира', $this->getBtValue($bt, 'kvartira')),
            'email' => array('Email', $this->getBtValue($bt, 'email')),
            'customer_note' => array('Комментарий', $this->getBtValue($bt, 'customer_note')),
            'coupon_code' => array('Купон', $this->getBtValue($bt, 'coupon_code')),
        );

        foreach ($enabled as $key) {
            if (!isset($fieldMap[$key])) {
                continue;
            }
            $label = $fieldMap[$key][0];
            $value = trim((string) $fieldMap[$key][1]);
            if ($value === '') {
                continue;
            }
            $lines[] = $label . ': ' . $value;
        }

        if (in_array('items', $enabled, true) && isset($order['items']) && is_array($order['items'])) {
            $itemsBlock = array();
            foreach ($order['items'] as $item) {
                $productName = isset($item->order_item_name) ? $item->order_item_name : '';
                $quantity = isset($item->product_quantity) ? $item->product_quantity : '';
                $price = isset($item->product_basePriceWithTax) ? $item->product_basePriceWithTax : '';
                $itemsBlock[] = $productName . "\nКоличество: " . $quantity . "\nЦена: " . $price . " руб.";
            }
            if ($itemsBlock !== array()) {
                $lines[] = '';
                foreach ($itemsBlock as $i => $itemText) {
                    $lines[] = $itemText;
                    if ($i !== count($itemsBlock) - 1) {
                        $lines[] = '';
                    }
                }
            }
        }

        if (in_array('total', $enabled, true)) {
            $total = isset($bt->order_total) ? ceil((float) $bt->order_total) : 0;
            $lines[] = '';
            $lines[] = '***';
            $lines[] = '';
            $lines[] = 'Итого: ' . $total . ' руб.';
        }

        $uall = trim(implode("\n", $lines));
        if ($uall === '') {
            return true;
        }

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('password', 'email', 'server')));
        $query->from($db->quoteName('#__maxbot_settings'));
        $db->setQuery($query);
        $settings = $db->loadAssoc();

        if (!is_array($settings) || empty($settings['password']) || empty($settings['server'])) {
            return true;
        }

        $req = array(
            'password' => (string) $settings['password'],
            'data' => $uall,
        );
        $senddata = json_encode($req);
        if ($senddata === false) {
            return true;
        }

        $this->sendDataToServer($senddata, (string) $settings['server']);

        return true;
    }

    private function getEnabledFields()
    {
        $raw = self::DEFAULT_FIELDS;
        if (isset($this->params) && is_object($this->params) && method_exists($this->params, 'get')) {
            $raw = $this->params->get('enabled_fields', self::DEFAULT_FIELDS);
        }

        if (is_array($raw)) {
            $parts = array_map(static function ($v) {
                return strtolower(trim((string) $v));
            }, $raw);
        } else {
            $parts = preg_split('/[\s,;]+/', strtolower((string) $raw)) ?: array();
        }

        $parts = array_values(array_filter($parts, static function ($v) {
            return $v !== '' && $v !== '0';
        }));

        return array_values(array_unique($parts));
    }

    private function getBtValue($bt, $property)
    {
        if (!is_object($bt) || !isset($bt->$property)) {
            return '';
        }

        return (string) $bt->$property;
    }
}
