{namespace name="frontend/heidelpay/account/devices/sepa_mandate"}

{block name="frontend_account_payment_content_heidelpay_vault_sepa"}
    <div class="panel heidelpay-vault--device-group">
        {block name="frontend_account_payment_content_heidelpay_vault_sepa_title"}
            <div class="panel--title">
                {s name="title"}{/s}
            </div>
        {/block}

        {block name="frontend_account_payment_content_heidelpay_vault_sepa_body"}
            <div class="panel--body">
                {foreach $devices as $sepaMandate}
                    <div class="panel has--border is--rounded heidelpay--card-wrapper">
                        <div class="panel--body">
                            {block name="frontend_account_payment_content_heidelpay_vault_sepa_body_iban"}
                                <div class="heidelpay-vault--sepa-iban is--bold">
                                    {s name="field/iban"}{/s} {$sepaMandate->getIban()}
                                </div>
                            {/block}
                            {block name="frontend_account_payment_content_heidelpay_vault_sepa_body_created"}
                                <div class="heidelpay-vault--sepa-date">
                                    {s name="field/date"}{/s} {$sepaMandate->getDate()}
                                </div>
                            {/block}
                            {block name="frontend_account_payment_content_heidelpay_vault_sepa_body_actions"}
                                <div class="heidelpay-vault--actions">
                                    <a href="{url controller=HeidelpayDeviceVault action=deleteDevice id=$sepaMandate->getId()}">{s name="link/delete" namespace="frontend/heidelpay/account/payment_device_vault"}{/s}</a>
                                </div>
                            {/block}
                        </div>
                    </div>
                {/foreach}
            </div>
        {/block}
    </div>
{/block}
