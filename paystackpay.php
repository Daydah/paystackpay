<?php
/**
 * @plugin Paystack Payment Plugin for the Pay per Download component
 * @author Adedayo Adeniyi
 * @copyright (C) Adedayo Adeniyi
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/
defined( '_JEXEC' ) or die;

//Review PayPal format at Payperdownload component path/site/models/pay.php
require_once (JPATH_ADMINISTRATOR . "/components/com_payperdownload/classes/debug.php");
// import the JPlugin class
jimport('joomla.event.plugin');


// Add a prefix to this class name to avoid conflict with other plugins
class joomla_pp_paystack_plugin_tracker {
    var $public_key;
    var $plugin_name;
    function __construct($plugin, $pk){
        //configure plugin name
        //configure public key
        $this->plugin_name = $plugin;
        $this->public_key = $pk;
    }

    function log_transaction_success($trx_ref){
        //send reference to logger along with plugin name and public key
        $url = "https://plugin-tracker.paystackintegrations.com/log/charge_success";
        $fields = [
            'plugin_name'  => $this->plugin_name,
            'transaction_reference' => $trx_ref,
            'public_key' => $this->public_key
        ];
        $fields_string = http_build_query($fields);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        //execute post
        $result = curl_exec($ch);
        //  echo $result;
    }
}


class plgPayperDownloadPlusPaystackPay extends JPlugin
{
	public function __construct(&$subject, $config = array())
    {
        parent::__construct($subject, $config);

        $this->loadLanguage();
	}

	public function onRenderPaymentForm($user, $license, $resource,
		$returnUrl, $thankyouUrl)
	{
		$siteUrl = JUri::base(); $package_price = array();
		$theuser = JFactory::getUser();
		$pmntinfo = $this->getPaystackPaymentInfo();
		$public_key = $pmntinfo->public_key;

		$user_id = 0;
		if($user)
		{
				$user_id = $user->id; $customer_email = $user->email;
				$thename = explode(" ",$user->name); $firstname = $thename[0];
			//	if(count($thename) > 1){$lastname = $thename[1];}
			}
			else{
				$user_id = $theuser->id; $customer_email = $theuser->email;
				$thename = explode(" ",$theuser->name); $firstname = $thename[0];
			}
		if($public_key && $customer_email)
		{
			$item_id = 0;	$name = ""; $damount = 0; $currency = ""; $description = "";
			$type = ""; $task = ""; $download_id = 0; $amount = 0;
			if($license || $resource)
			{
				if($resource)
				{
					$damount = $resource->resource_price;
					$currency = $resource->resource_price_currency; //$currency = $pmntinfo->currency;
					$item_id = $resource->resource_license_id;
					$name = $resource->resource_name;
					$download_id = $resource->download_id;
					if($resource->alternate_resource_description)
						$description = $resource->alternate_resource_description;
					else
						$description = $resource->resource_description;
					$task = "confirmres";
					$type = "resource";
				}
				else
				{
					$damount = $license->price;
					$currency = $license->currency_code; //$currency = $pmntinfo->currency;
					$item_id = $license->license_id;
					$name = $license->license_name;
					$description = $license->description;
					$task = "confirm";
					$type = "license";
				}
				$package_price = $this->calcFinalAmount($damount, $pmntinfo->ps_extra, $pmntinfo->ps_extratype, $pmntinfo->ps_extraval);
				$amount = $package_price[0] * 100;
				$amount = (int)$amount;
				$returnBase64Coded =  base64_encode($returnUrl);

				$callbackurl = JURI::root() . 'index.php?option=com_payperdownload&amp;gateway=paystack&amp;task=' . $task;
				$callbackurl .= '&item_id=' . htmlentities($item_id);
				$callbackurl .= '&item_type=' . $type;
				$callbackurl .= '&user_id=' . htmlentities($user_id);
				$callbackurl .= '&amount=' . htmlentities($damount);
				$callbackurl .= '&currency=' . htmlentities($currency);
				$callbackurl .= '&r=' . htmlentities($returnBase64Coded);

				if ($thankyouUrl) {
				    $returnParams = '';
				    if ($type == 'license') {
				        if (strstr($thankyouUrl, "?") === false) {
				            $returnParams = '?lid=' . (int)$license->license_id;
				        } else {
				            $returnParams = '&lid=' . (int)$license->license_id;
				        }
				    }
				    $callbackurl .= '&redirect=' . htmlentities(base64_encode($thankyouUrl . $returnParams));
				}

				if ($download_id) {
				    $callbackurl .= '&download_id=' . (int)$download_id;
				}

				$the_reference = 'ppdp'.floor(mt_rand()*10 + 1);//''+Math.floor((Math.random() * 1000000000) + 1);
				//put the reference in a session
    	       	$session = JFactory::getSession();
    	       	$session->set('session_transaction_id',$the_reference); //dump($the_reference,"the created reference");

                //construct the variable containing the form

    	       	JFactory::getDocument()->addScript('https://js.paystack.co/v1/inline.js');

    	       	if (count($thename) > 1) {
    	       	    $lastname = $thename[1];
    	       	}

    	       	$js_declaration = <<< JS
                    function payWithPaystack() {
                        var handler = PaystackPop.setup({
                            key: "$public_key",
                            email: "$customer_email",
                            amount: "$amount",
                            ref: "$the_reference",
                            currency: "$currency",
                            firstname: "$firstname",
                            lastname: "$lastname",
                            metadata: {
                                "ecommerce_platform":"Joomla 3",
                                "payment_plugin": "Paystackpay for PayPerDownload",
                                "author": "Daydah",
                                custom_fields: [
                                    {
                                        display_name: "Plugin",
                                        variable_name: "plugin",
                                        value: "Paystackpay for PayPerDownload"
                                    }
                                ]
                            },
                            callback: function(response){
                                document.getElementById("paystack-payment_form").submit();
                            },
                            onClose: function(){
                                /* cancelling by the buyer */
                            }
                        });
                        handler.openIframe();
                    }

                    window.addEventListener('load', function() {
                        document.querySelector('#paystack-pay-btn').addEventListener('click', function () {
                            payWithPaystack();
                        });
                    }, false);
JS;
    	       	JFactory::getDocument()->addScriptDeclaration($js_declaration);

                //$tosend ='<div class="payment-heading">'.$redirectHeading.'</div>';
    	       	echo '<form method="post" action="'.$callbackurl.'" name="paystack-payment_form" id="paystack-payment_form">';

    	       	echo $this->_loadTemplate('default.php');

                //add the hidden form fields too
    	       	echo '<input type="hidden" name="item_id" value="'.htmlentities($item_id).'"/>';
    	       	echo '<input type="hidden" name="item_type" value="'.htmlentities($type).'"/>';
    	       	echo '<input type="hidden" name="user_id" value="'.htmlentities($user_id).'"/>';
    	       	echo '<input type="hidden" name="damount" value="'.htmlentities($damount).'"/>';
    	       	echo '<input type="hidden" name="currency" value="'.htmlentities($currency).'"/>';
    	       	echo '<input type="hidden" name="r" value="'.htmlentities($returnBase64Coded).'"/>';
    	       	echo '<input type="hidden" name="redirect" value="'.htmlentities($returnBase64Coded).'"/>';
    	       	echo '<input type="hidden" name="the-fee" value="'.htmlentities($package_price[1]).'"/>';

    			if($download_id)
    			{
    			    echo '<input type="hidden" name="download_id" value="'.(int)$download_id.'/>';
    			}

    			echo '</form>';
			}
		}
	}

	public function onPaymentReceived($gateway, &$dealt, &$payed,
		&$user_id, &$license_id, &$resource_id, &$transactionId,
		&$response, &$validate_response, &$status, &$amount,
		&$tax, &$fee, &$currency)
	{
	    if($gateway == "paystack")
		{
		    $jinput = JFactory::getApplication()->input;

			$dealt = true;
			$payed = false;
			$amount = $jinput->getInt('damount');
			$currency = $jinput->getString('currency');
			$user_id = $jinput->getInt("user_id");
			$item_type = $jinput->getString("item_type");
			$item_id = $jinput->getInt("item_id");
			$download_id = $jinput->getInt("download_id");
			$license_id = 0;
			$resource_id = 0;
			$tax = 0;
			if($item_type == "resource")
				{$resource_id = $item_id;}
			else
				{$license_id = $item_id;}
				$session = JFactory::getSession();
			$thetransactionid = $session->get('session_transaction_id');

			$pmntinfo1 = $this->getPaystackPaymentInfo();

			$secret_key = $pmntinfo1->secret_key;
			$public_key = $pmntinfo1->public_key;
			if($secret_key && $thetransactionid)
			{
				try
				{
					$transData = $this->verifyPaystackTransaction($thetransactionid, $secret_key);
					$sentreference = '';
					if(property_exists($transData, 'reference'))
					{
						$sentreference = $transData->reference;
					}
					else if(property_exists($transData, 'paystack-reference'))
					{
						$sentreference = $transData->paystack-reference;
					}

					if (
						(!property_exists($transData, 'error')) &&
					 	property_exists($transData, 'status') &&
						 ($transData->status === 'success') &&
						 (strpos($transData->reference, $thetransactionid) === 0)
						 )
					{

						//pstk-logger
						$pstk_logger = new joomla_pp_paystack_plugin_tracker('Paystackpay for PayPerDownload', $public_key);
						$pstk_logger->log_transaction_success($sentreference);
						//-------------



						// Update order status - From pending to complete
						$amount = $amount;// / 100.0;
						$payed = true;
						$transactionId = $sentreference;
						$fee = $jinput->getInt('the-fee');
						$status = "COMPLETED";
						$validate_response = "VERIFIED";
						$response = json_encode($transData);
						$payerEmail = $transData->customer->email;
						if(!$payerEmail){ $payerEmail = $theuser->email;}
				    }
				    else if (property_exists($transData, 'error') || (property_exists($transData, 'status') && $transData->status === 'failed'))
		 		 {
					 $status = "FAILED";
 					 $response = $transData->gateway_response;
				 }

					if($download_id)
					{
						$session = JFactory::getSession();
						$transactions = $session->get("trans", array());
						$transactions["$transactionId"] =
							array("download_id" => $download_id,
								  "payeremail" => $payerEmail
							);
						$session->set("trans", $transactions);
					}
				}
				catch (Exception $e)
				{
					$status = "FAILED";
					$response = $e->getMessage();
				}
			}
		}
	}

	public function onGetPayerEmail($transactionId, &$payer_email)
	{
		$session = JFactory::getSession();
		$transactions = $session->get("trans", array());
		if(isset($transactions[$transactionId]))
		{
			$payer_email = $transactions[$transactionId]["payeremail"];
			unset($transactions[$transactionId]);
			$session->set("trans", $transactions);
		}
	}

	public function onGetDownloadLinkId($transactionId, &$download_id)
	{
		$session = JFactory::getSession();
		$transactions = $session->get("trans", array());
		if(isset($transactions[$transactionId]))
		{
			$download_id = $transactions[$transactionId]["download_id"];
		}
	}

	public function calcFinalAmount($initialval, $extra1, $extratype, $extraval)
	{
	    $finvalue = 0; $calcpercent = 0; $finvsend = array(); $dextra = 0;
		//check if there extra switch is on or off
		if($extra1 == 0){
			//then extra switch is off.
			$finvalue = $initialval;
		}
		else{ //the extra switch is on, so we need to calculate final value
			//check type
			if($extratype == 0){
				//then the type is a percentage
				//calculate percentage value
				$dextra = ($extraval * $initialval)/100;
				$finvalue = $initialval + $dextra;
			}
			else{//then the type is a fixed amount
				$dextra = $extraval;
				$finvalue = $initialval + $extraval;
			}
		}
		array_push($finvsend,$finvalue,$dextra);
		return $finvsend;
	}

	/*
	 * @param string $secret_key is either the demo or live secret key from your dashboard. $transactionid is the transaction reference code sent to the API previously
	 */

     public function verifyPaystackTransaction($transactionid, $secret_key)
     {
    	 $transactionStatus  = new stdClass();
    	 $transactionStatus->error = "";
    	 // try a file_get verification
    	 $opts = array(
    			'http' => array(
    					'method' => "GET",
    					'header' => "Authorization: Bearer " . $secret_key
    			)
    		);

    		$context  = stream_context_create($opts);
    		$url      = "https://api.paystack.co/transaction/verify/" . $transactionid;
    		$response = file_get_contents($url, false, $context);

    		// if file_get didn't work, try curl
    		if (!$response) {
    		    $ch = curl_init();

    			curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . $transactionid);
    			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    					'Authorization: Bearer ' . $secret_key
    			));
    			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    			curl_setopt($ch, CURLOPT_HEADER, false);

    			// Make sure CURL_SSLVERSION_TLSv1_2 is defined as 6
    			// cURL must be able to use TLSv1.2 to connect
    			// to Paystack servers
    			if (!defined('CURL_SSLVERSION_TLSv1_2')) {
    					define('CURL_SSLVERSION_TLSv1_2', 6);
    			}
    			curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
    			// exec the cURL
    			$response = curl_exec($ch);
    			// should be 0
    			if (curl_errno($ch)) {
    					// curl ended with an error
    					$transactionStatus->error = "cURL said:" . curl_error($ch);
    			}
    			//close connection
    			curl_close($ch);
    	}

    	if ($response)
    	{
    		$body = json_decode($response);
    		if (!$body->status)
    		{
    			// paystack has an error message for us
    			$transactionStatus->error = "Paystack API said: " . $body->message;
    		} else {
    			// get body returned by Paystack API
    			$transactionStatus = $body->data;
    		}
    	} else {
    		// no response
    		$transactionStatus->error = $transactionStatus->error . " : No response";
    	}

    	return $transactionStatus;
     }

    public function getPaystackPaymentInfo()
    {
    	$pddpinfo = new StdClass();
    	$pddpinfo->pmode = $this->params->get('ppdp_test_mode');//get test mode and use to get keys

    	switch($pddpinfo->pmode)
    	{
    		case 0: //its in test mode
    			$pddpinfo->secret_key = $this->params->get('ppdp_test_secret_key');
    			$pddpinfo->public_key = $this->params->get('ppdp_test_public_key'); break;
    		case 1://its live
    			$pddpinfo->secret_key = $this->params->get('ppdp_live_secret_key');
    			$pddpinfo->public_key = $this->params->get('ppdp_live_public_key'); break;
    		default: //its in test mode
    			$pddpinfo->secret_key = $this->params->get('ppdp_test_secret_key');
    			$pddpinfo->public_key = $this->params->get('ppdp_test_public_key'); break;
    	}
    	//$pddpinfo->notify_email = $this->params->get('notify_email', '');

    	$pddpinfo->ps_extra = $this->params->get('paystack_extra_yes_no');
    	$pddpinfo->ps_extratype = $this->params->get('paystack_extra_type');
    	$pddpinfo->ps_extraval = $this->params->get('paystack_extra_charges_value');

    	//$pddpinfo->currency = $this->params['paystack_currcode'];
    	return $pddpinfo;
    }

    private function _loadTemplate($file = null, $variables = array())
    {
        $template = JFactory::getApplication()->getTemplate();
        $overridePath = JPATH_THEMES.'/'.$template.'/html/plg_payperdownloadplus_paystackpay';

        if (is_file($overridePath.'/'.$file)) {
            $file = $overridePath.'/'.$file;
        } else {
            $file = __DIR__.'/tmpl/'.$file;
        }

        unset($template);
        unset($overridePath);

        if (!empty($variables)) {
            foreach ($variables as $name => $value) {
                $$name = $value;
            }
        }

        unset($variables);
        unset($name);
        unset($value);
        if (isset($this->this)) {
            unset($this->this);
        }

        @ob_start();
        include $file;
        $html = ob_get_contents();
        @ob_end_clean();

        return $html;
    }

}

?>
