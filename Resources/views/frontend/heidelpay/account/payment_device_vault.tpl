{block name="frontend_account_payment_content_heidelpay_vault"}
    <div class="panel has--border is--wide heidelpay-vault is--rounded">
        {block name="frontend_account_payment_content_heidelpay_vault_title"}
            <div class="panel--title is--underline">
                {s name="title"}{/s}
            </div>
        {/block}

        {block name="frontend_account_payment_content_heidelpay_vault_body"}
            <div>
                {foreach $heidelpayVault as $deviceType => $paymentDeviceList}
                    {include file="frontend/heidelpay/account/devices/{$deviceType}.tpl" devices=$paymentDeviceList}
                {/foreach}
            </div>
        {/block}
    </div>
{/block}
