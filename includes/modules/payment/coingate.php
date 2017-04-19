<?php

class coingate
{
  public $code;
  public $title;
  public $description;
  public $enabled;

  function coingate()
  {
    $this->code             = 'coingate';
    $this->title            = MODULE_PAYMENT_COINGATE_TEXT_TITLE;
    $this->description      = MODULE_PAYMENT_COINGATE_TEXT_DESCRIPTION;
    $this->app_id           = MODULE_PAYMENT_COINGATE_APP_ID;
    $this->api_key          = MODULE_PAYMENT_COINGATE_API_KEY;
    $this->api_secret       = MODULE_PAYMENT_COINGATE_API_SECRET;
    $this->receive_currency = MODULE_PAYMENT_COINGATE_RECEIVE_CURRENCY;
    $this->sort_order       = MODULE_PAYMENT_COINGATE_SORT_ORDER;
    $this->testMode         = ((MODULE_PAYMENT_COINGATE_TEST == 'True') ? true : false);
    $this->enabled          = ((MODULE_PAYMENT_COINGATE_STATUS == 'True') ? true : false);
  }

  function javascript_validation()
  {
    return false;
  }

  function selection()
  {
    return array('id' => $this->code, 'module' => $this->title);
  }

  function pre_confirmation_check()
  {
    return false;
  }

  function confirmation()
  {
    return false;
  }

  function process_button()
  {
    return false;
  }

  function before_process()
  {
    return false;
  }

  function after_process()
  {
    global $insert_id, $order;

    $info = $order->info;

    $configuration = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key='STORE_NAME' limit 1");
    $configuration = tep_db_fetch_array($configuration);
    $products = tep_db_query("select oc.products_id, oc.products_quantity, pd.products_name from " . TABLE_ORDERS_PRODUCTS . " as oc left join " . TABLE_PRODUCTS_DESCRIPTION . " as pd on pd.products_id=oc.products_id  where orders_id=" . intval($insert_id));

    $description = array();
    foreach ($products as $product) {
      $description[] = $product['products_quantity'] . ' × ' . $product['products_name'];
    }

    $callback = tep_href_link('coingate_callback.php', $parameters='', $connection='NONSSL', $add_session_id=true, $search_engine_safe=true, $static=true );

    $params = array(
      'order_id'         => $insert_id,
      'price'            => number_format($info['total'], 2, '.', ''),
      'currency'         => $info['currency'],
      'receive_currency' => MODULE_PAYMENT_COINGATE_RECEIVE_CURRENCY,
      'callback_url'     => $callback . "?token=" . MODULE_PAYMENT_COINGATE_CALLBACK_SECRET,
      'cancel_url'       => tep_href_link(FILENAME_CHECKOUT_PAYMENT),
      'success_url'      => tep_href_link(FILENAME_CHECKOUT_SUCCESS),
      'title'            => $configuration->fields['configuration_value'] . ' Order #' . $insert_id,
      'description'      => join($description, ', ')
    );

    require_once(dirname(__FILE__) . "/CoinGate/init.php");
    require_once(dirname(__FILE__) . "/CoinGate/version.php");

    $order = \CoinGate\Merchant\Order::createOrFail($params, array(), array(
      'app_id' => MODULE_PAYMENT_COINGATE_APP_ID,
      'api_key' => MODULE_PAYMENT_COINGATE_API_KEY,
      'api_secret' => MODULE_PAYMENT_COINGATE_API_SECRET,
      'environment' => MODULE_PAYMENT_COINGATE_TEST == "True" ? 'sandbox' : 'live',
      'user_agent' => 'CoinGate - osCommerce Extension v' . COINGATE_OSCOMMERCE_EXTENSION_VERSION));

    $_SESSION['cart']->reset(true);
    tep_redirect($order->payment_url);

    return false;
  }

  function get_error()
  {
    return false;
  }

  function check()
  {
    if (!isset($this->_check)) {
      $check_query  = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_COINGATE_STATUS'");
      $this->_check = tep_db_num_rows($check_query);
    }

    return $this->_check;
  }

  function install()
  {
    $callbackSecret = md5('zencart_' . mt_rand());

    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable CoinGate Module', 'MODULE_PAYMENT_COINGATE_STATUS', 'False', 'Enable the CoinGate bitcoin plugin?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('CoinGate APP ID', 'MODULE_PAYMENT_COINGATE_APP_ID', '0', 'Your CoinGate APP ID', '6', '0', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('CoinGate API Key', 'MODULE_PAYMENT_COINGATE_API_KEY', '0', 'Your CoinGate API Key', '6', '0', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('CoinGate APP Secret', 'MODULE_PAYMENT_COINGATE_API_SECRET', '0', 'Your CoinGate API Secret', '6', '0', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Receive Currency', 'MODULE_PAYMENT_COINGATE_RECEIVE_CURRENCY', 'BTC', 'Currency you want to receive when making withdrawal at CoinGate. Please take a note what if you choose EUR or USD you will be asked to verify your business before making a withdrawal at CoinGate.', '6', '0', 'tep_cfg_select_option(array(\'EUR\', \'USD\', \'BTC\'), ', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_COINGATE_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '8', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable test mode?', 'MODULE_PAYMENT_COINGATE_TEST', 'False', 'Enable test mode to test on sandbox.coingate.com. Please note, that for test mode you must generate separate API credentials on sandbox.coingate.com. API credentials generated on coingame.com will not work for test mode.', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Pending Order Status', 'MODULE_PAYMENT_COINGATE_PENDING_STATUS_ID', '" . intval(DEFAULT_ORDERS_STATUS_ID) .  "', 'Status in your store when CoinGate order status is pending.<br />(\'Pending\' recommended)', '6', '5', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Paid Order Status', 'MODULE_PAYMENT_COINGATE_PAID_STATUS_ID', '2', 'Status in your store when CoinGate order status is paid.<br />(\'Processing\' recommended)', '6', '6', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Invalid Order Status', 'MODULE_PAYMENT_COINGATE_INVALID_STATUS_ID', '2', 'Status in your store when CoinGate order status is invalid.<br />(\'Failed\' recommended)', '6', '6', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Expired Order Status', 'MODULE_PAYMENT_COINGATE_EXPIRED_STATUS_ID', '2', 'Status in your store when CoinGate order status is expired.<br />(\'Expired\' recommended)', '6', '6', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Canceled Order Status', 'MODULE_PAYMENT_COINGATE_CANCELED_STATUS_ID', '2', 'Status in your store when CoinGate order status is canceled.<br />(\'Canceled\' recommended)', '6', '6', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Refunded Order Status', 'MODULE_PAYMENT_COINGATE_REFUNDED_STATUS_ID', '2', 'Status in your store when CoinGatet order status is refunded.<br />(\'Refunded\' recommended)', '6', '6', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Callback Secret Key (do not edit)', 'MODULE_PAYMENT_COINGATE_CALLBACK_SECRET', '$callbackSecret', '', '6', '6', now(), 'coingate_censorize')");
  }

  function remove ()
  {
    tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key LIKE 'MODULE\_PAYMENT\_COINGATE\_%'");
  }

  function keys()
  {
    return array(
      'MODULE_PAYMENT_COINGATE_STATUS',
      'MODULE_PAYMENT_COINGATE_APP_ID',
      'MODULE_PAYMENT_COINGATE_API_KEY',
      'MODULE_PAYMENT_COINGATE_API_SECRET',
      'MODULE_PAYMENT_COINGATE_RECEIVE_CURRENCY',
      'MODULE_PAYMENT_COINGATE_SORT_ORDER',
      'MODULE_PAYMENT_COINGATE_TEST',
      'MODULE_PAYMENT_COINGATE_PENDING_STATUS_ID',
      'MODULE_PAYMENT_COINGATE_PAID_STATUS_ID',
      'MODULE_PAYMENT_COINGATE_INVALID_STATUS_ID',
      'MODULE_PAYMENT_COINGATE_EXPIRED_STATUS_ID',
      'MODULE_PAYMENT_COINGATE_CANCELED_STATUS_ID',
      'MODULE_PAYMENT_COINGATE_REFUNDED_STATUS_ID',
      'MODULE_PAYMENT_COINGATE_CALLBACK_SECRET'
    );
  }
}
function coingate_censorize($value) {
  return "(hidden for security reasons)";
}
