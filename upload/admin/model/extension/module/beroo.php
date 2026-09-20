<?php

/* OpenCart 2.3, 3.0 */

class ModelExtensionModuleBeroo extends Model
{
    public function install() {
        if (version_compare(VERSION,'3.0.0.0','>=')) {
            $this->load->model('setting/event');
            $this->model_setting_event->addEvent('beroo_add_order_history', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/module/beroo/eventAddOrderHistory');
            $this->model_setting_event->addEvent('beroo_add_product', 'admin/model/catalog/product/addProduct/after', 'extension/module/beroo/eventEditProduct');
            $this->model_setting_event->addEvent('beroo_pre_edit_product', 'admin/model/catalog/product/editProduct/before', 'extension/module/beroo/eventPreEditProduct');
            $this->model_setting_event->addEvent('beroo_edit_product', 'admin/model/catalog/product/editProduct/after', 'extension/module/beroo/eventEditProduct');
            $this->model_setting_event->addEvent('beroo_delete_product', 'admin/model/catalog/product/deleteProduct/before', 'extension/module/beroo/eventDeleteProduct');
            $this->model_setting_event->addEvent('beroo_add_order', 'catalog/model/checkout/order/addOrder/after', 'extension/module/beroo/eventAddOrder');
        } else {
            $this->load->model('extension/event');

            $this->model_extension_event->addEvent('beroo_add_order_history', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/module/beroo/eventAddOrderHistory');
            $this->model_extension_event->addEvent('beroo_add_product', 'admin/model/catalog/product/addProduct/after', 'extension/module/beroo/eventEditProduct');
            $this->model_extension_event->addEvent('beroo_pre_edit_product', 'admin/model/catalog/product/editProduct/before', 'extension/module/beroo/eventPreEditProduct');
            $this->model_extension_event->addEvent('beroo_edit_product', 'admin/model/catalog/product/editProduct/after', 'extension/module/beroo/eventEditProduct');
            $this->model_extension_event->addEvent('beroo_delete_product', 'admin/model/catalog/product/deleteProduct/before', 'extension/module/beroo/eventDeleteProduct');
            $this->model_extension_event->addEvent('beroo_add_order', 'catalog/model/checkout/order/addOrder/after', 'extension/module/beroo/eventAddOrder');
        }
    }

    public function uninstall() {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'beroo'");
        if (version_compare(VERSION,'3.0.0.0','>=')) {
            $this->load->model('setting/event');
            $this->model_setting_event->deleteEventByCode('beroo_add_order_history');
            $this->model_setting_event->deleteEventByCode('beroo_add_product');
            $this->model_setting_event->deleteEventByCode('beroo_edit_product');
            $this->model_setting_event->deleteEventByCode('beroo_pre_edit_product');
            $this->model_setting_event->deleteEventByCode('beroo_delete_product');
            $this->model_setting_event->deleteEventByCode('beroo_add_order');
        } else {
            $this->load->model('extension/event');
            $this->model_extension_event->deleteEvent('beroo_add_order_history');
            $this->model_extension_event->deleteEvent('beroo_add_product');
            $this->model_extension_event->deleteEvent('beroo_edit_product');
            $this->model_extension_event->deleteEvent('beroo_pre_edit_product');
            $this->model_extension_event->deleteEvent('beroo_delete_product');
            $this->model_extension_event->deleteEvent('beroo_add_order');
        }
    }
}