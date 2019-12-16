<?php
/**
 * @plugin Paystack Payment Plugin for the Pay per Download component
 * @author Adedayo Adeniyi
 * @copyright (C) Adedayo Adeniyi
 * @copyright (C) 2011 Simplify Your Web, Inc. All rights reserved
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die;
?>
<button id="paystack-pay-btn" type="button">
	<?php echo JText::_('PLG_PAYPERDOWNLOADPLUS_PAYSTACKPAY_PAYWITHPAYSTACK'); ?>
</button>
<?php if ($additional_fee > 0) : ?>
	<?php echo JText::sprintf('PLG_PAYPERDOWNLOADPLUS_PAYSTACKPAY_ADDITIONALFEEWILLAPPLY', $additional_fee, $currency); ?>
<?php endif; ?>