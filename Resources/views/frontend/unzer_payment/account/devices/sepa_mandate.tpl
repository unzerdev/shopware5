{block name="frontend_account_payment_content_unzer_payment_vault_sepa"}
    <div class="panel unzer-payment-vault--device-group">
        {block name="frontend_account_payment_content_unzer_payment_vault_sepa_title"}
            <div class="panel--title">
                {s name="title"}{/s}
            </div>
        {/block}

        {block name="frontend_account_payment_content_unzer_payment_vault_sepa_body"}
            <div class="panel--body">
                {foreach $devices as $sepaMandate}
                    <div class="panel has--border is--rounded unzer-payment--card-wrapper">
                        <div class="panel--body">
                            {block name="frontend_account_payment_content_unzer_payment_vault_sepa_body_iban"}
                                <div class="unzer-payment-vault--sepa-iban is--bold">
                                    {s name="field/iban"}{/s} {$sepaMandate->getIban()}
                                </div>
                            {/block}
                            {block name="frontend_account_payment_content_unzer_payment_vault_sepa_body_created"}
                                <div class="unzer-payment-vault--sepa-date">
                                    {s name="field/date"}{/s} {$sepaMandate->getDate()}
                                </div>
                            {/block}
                            {block name="frontend_account_payment_content_unzer_payment_vault_sepa_body_actions"}
                                <div class="unzer-payment-vault--actions">
                                    <a href="{url controller=UnzerPaymentDeviceVault action=deleteDevice id=$sepaMandate->getId()}">{s name="link/delete" namespace="frontend/unzer_payment/account/payment_device_vault"}{/s}</a>
                                </div>
                            {/block}
                        </div>
                    </div>
                {/foreach}
            </div>
        {/block}
    </div>
{/block}
