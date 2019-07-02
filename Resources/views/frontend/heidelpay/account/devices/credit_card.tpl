{block name="frontend_account_payment_content_heidelpay_vault_credit_card"}
    <div class="panel heidelpay-vault--device-group">
        {block name="frontend_account_payment_content_heidelpay_vault_credit_card_title"}
            <div class="panel--title">
                {s name="title"}{/s}
            </div>
        {/block}

        {block name="frontend_account_payment_content_heidelpay_vault_credit_card_body"}
            <div class="panel--body">
                {foreach $devices as $creditCard}
                    <div class="panel has--border is--rounded heidelpay--card-wrapper">
                        <div class="panel--body">
                            {block name="frontend_account_payment_content_heidelpay_vault_credit_card_body_number"}
                                <div class="heidelpay-vault--credit-card-number is--bold">
                                    {s name="field/number"}{/s} {$creditCard->getNumber()}
                                </div>
                            {/block}
                            {block name="frontend_account_payment_content_heidelpay_vault_credit_card_body_holder"}
                                <div class="heidelpay-vault--credit-card-holder">
                                    {s name="field/holder"}{/s} {$creditCard->getHolder()}
                                </div>
                            {/block}
                            {block name="frontend_account_payment_content_heidelpay_vault_credit_card_body_expiry"}
                                <div class="heidelpay-vault--credit-card-expiry">
                                    {s name="field/expiryDate"}{/s} {$creditCard->getExpiryDate()}
                                </div>
                            {/block}
                            {block name="frontend_account_payment_content_heidelpay_vault_credit_card_body_actions"}
                                <div class="heidelpay-vault--credit-card-actions">
                                    <a href="{url controller=HeidelpayVault action=deleteDevice id=$creditCard->getId()}">{s name="link/delete"}{/s}</a>
                                </div>
                            {/block}
                        </div>
                    </div>
                {/foreach}
            </div>
        {/block}
    </div>
{/block}
