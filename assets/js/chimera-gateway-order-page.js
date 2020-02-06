/*
 * Copyright (c) 2018, Ryo Currency Project
*/
function chimera_showNotification(message, type='success') {
    var toast = jQuery('<div class="' + type + '"><span>' + message + '</span></div>');
    jQuery('#chimera_toast').append(toast);
    toast.animate({ "right": "12px" }, "fast");
    setInterval(function() {
        toast.animate({ "right": "-400px" }, "fast", function() {
            toast.remove();
        });
    }, 2500)
}
function chimera_showQR(show=true) {
    jQuery('#chimera_qr_code_container').toggle(show);
}
function chimera_fetchDetails() {
    var data = {
        '_': jQuery.now(),
        'order_id': chimera_details.order_id
    };
    jQuery.get(chimera_ajax_url, data, function(response) {
        if (typeof response.error !== 'undefined') {
            console.log(response.error);
        } else {
            chimera_details = response;
            chimera_updateDetails();
        }
    });
}

function chimera_updateDetails() {

    var details = chimera_details;

    jQuery('#chimera_payment_messages').children().hide();
    switch(details.status) {
        case 'unpaid':
            jQuery('.chimera_payment_unpaid').show();
            jQuery('.chimera_payment_expire_time').html(details.order_expires);
            break;
        case 'partial':
            jQuery('.chimera_payment_partial').show();
            jQuery('.chimera_payment_expire_time').html(details.order_expires);
            break;
        case 'paid':
            jQuery('.chimera_payment_paid').show();
            jQuery('.chimera_confirm_time').html(details.time_to_confirm);
            jQuery('.button-row button').prop("disabled",true);
            break;
        case 'confirmed':
            jQuery('.chimera_payment_confirmed').show();
            jQuery('.button-row button').prop("disabled",true);
            break;
        case 'expired':
            jQuery('.chimera_payment_expired').show();
            jQuery('.button-row button').prop("disabled",true);
            break;
        case 'expired_partial':
            jQuery('.chimera_payment_expired_partial').show();
            jQuery('.button-row button').prop("disabled",true);
            break;
    }

    jQuery('#chimera_exchange_rate').html('1 CMRA = '+details.rate_formatted+' '+details.currency);
    jQuery('#chimera_total_amount').html(details.amount_total_formatted);
    jQuery('#chimera_total_paid').html(details.amount_paid_formatted);
    jQuery('#chimera_total_due').html(details.amount_due_formatted);

    jQuery('#chimera_integrated_address').html(details.integrated_address);

    if(turtlecoin_show_qr) {
        var qr = jQuery('#chimera_qr_code').html('');
        new QRCode(qr.get(0), details.qrcode_uri);
    }

    if(details.txs.length) {
        jQuery('#chimera_tx_table').show();
        jQuery('#chimera_tx_none').hide();
        jQuery('#chimera_tx_table tbody').html('');
        for(var i=0; i < details.txs.length; i++) {
            var tx = details.txs[i];
            var height = tx.height == 0 ? 'N/A' : tx.height;
	    var explorer_url = chimera_explorer_url+'/transaction.html?hash='+tx.txid;
            var row = ''+
                '<tr>'+
                '<td style="word-break: break-all">'+
                '<a href="'+explorer_url+'" target="_blank">'+tx.txid+'</a>'+
                '</td>'+
                '<td>'+height+'</td>'+
                '<td>'+tx.amount_formatted+' TRTL</td>'+
                '</tr>';

            jQuery('#chimera_tx_table tbody').append(row);
        }
    } else {
        jQuery('#chimera_tx_table').hide();
        jQuery('#chimera_tx_none').show();
    }

    // Show state change notifications
    var new_txs = details.txs;
    var old_txs = chimera_order_state.txs;
    if(new_txs.length != old_txs.length) {
        for(var i = 0; i < new_txs.length; i++) {
            var is_new_tx = true;
            for(var j = 0; j < old_txs.length; j++) {
                if(new_txs[i].txid == old_txs[j].txid && new_txs[i].amount == old_txs[j].amount) {
                    is_new_tx = false;
                    break;
                }
            }
            if(is_new_tx) {
                chimera_showNotification('Transaction received for '+new_txs[i].amount_formatted+' CMRA');
            }
        }
    }

    if(details.status != chimera_order_state.status) {
        switch(details.status) {
            case 'paid':
                chimera_showNotification('Your order has been paid in full');
                break;
            case 'confirmed':
                chimera_showNotification('Your order has been confirmed');
                break;
            case 'expired':
            case 'expired_partial':
                chimera_showNotification('Your order has expired', 'error');
                break;
        }
    }

    chimera_order_state = {
        status: chimera_details.status,
        txs: chimera_details.txs
    };

}
jQuery(document).ready(function($) {
    if (typeof chimera_details !== 'undefined') {
        chimera_order_state = {
            status: chimera_details.status,
            txs: chimera_details.txs
        };
        setInterval(chimera_fetchDetails, 30000);
        chimera_updateDetails();
        new ClipboardJS('.clipboard').on('success', function(e) {
            e.clearSelection();
            if(e.trigger.disabled) return;
            switch(e.trigger.getAttribute('data-clipboard-target')) {
                case '#chimera_integrated_address':
                    chimera_showNotification('Copied destination address!');
                    break;
                case '#chimera_total_due':
                    chimera_showNotification('Copied total amount due!');
                    break;
            }
            e.clearSelection();
        });
    }
});
