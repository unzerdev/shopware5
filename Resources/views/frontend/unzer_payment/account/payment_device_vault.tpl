{block name="frontend_account_payment_content_unzer_payment_vault"}
    <div class="panel has--border is--wide unzer-payment-vault is--rounded">
        {block name="frontend_account_payment_content_unzer_payment_vault_title"}
            <div class="panel--title is--underline">
                {s name="title"}{/s}
            </div>
        {/block}

        {block name="frontend_account_payment_content_unzer_payment_vault_body"}
            <div>
                {foreach $unzerPaymentVault as $deviceType => $paymentDeviceList}
                    {include file="frontend/unzer_payment/account/devices/{$deviceType}.tpl" devices=$paymentDeviceList}
                {/foreach}
            </div>
        {/block}
    </div>
{/block}
