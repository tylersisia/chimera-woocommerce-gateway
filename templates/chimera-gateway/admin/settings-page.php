<?php foreach($errors as $error): ?>
<div class="error"><p><strong>Chimera Gateway Error</strong>: <?php echo $error; ?></p></div>
<?php endforeach; ?>

<h1>Chimera Gateway Settings</h1>

<div style="border:1px solid #ddd;padding:5px 10px;">
    <?php
         echo 'Wallet height: ' . $balance['height'] . '</br>';
         echo 'Your balance is: ' . $balance['balance'] . '</br>';
         echo 'Unlocked balance: ' . $balance['unlocked_balance'] . '</br>';
         ?>
</div>

<table class="form-table">
    <?php echo $settings_html ?>
</table>

<h4><a href="https://github.com/afterconnery/chimera-woocommerce-gateway">Learn more about using the Chimera payment gateway</a></h4>

<script>
function chimeraUpdateFields() {
    var useChimeraPrices = jQuery("#woocommerce_chimera_gateway_use_chimera_price").is(":checked");
    if(useChimeraPrices) {
        jQuery("#woocommerce_chimera_gateway_use_chimera_price_decimals").closest("tr").show();
    } else {
        jQuery("#woocommerce_chimera_gateway_use_chimera_price_decimals").closest("tr").hide();
    }
}
chimeraUpdateFields();
jQuery("#woocommerce_chimera_gateway_use_chimera_price").change(chimeraUpdateFields);
</script>

<style>
#woocommerce_chimera_gateway_chimera_address,
#woocommerce_chimera_gateway_viewkey {
    width: 100%;
}
</style>
