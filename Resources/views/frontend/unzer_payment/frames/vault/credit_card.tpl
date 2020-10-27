{block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_vault"}
    <div class="unzer-payment--credit-card-vault">
        {block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_vault_message"}
            <div class="unzer-payment--vault-message">
                {s name="customerMessage"}{/s}
            </div>
        {/block}
        {foreach $unzerPaymentVault['credit_card'] as $creditCard}
            {block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_vault_card"}
                <div class="unzer-payment--credit-card-vault-item">
                    {block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_vault_card_radio_input"}
                        <input type="radio" id="{$creditCard->getTypeId()}" name="cardSelection"{if $creditCard@first} checked="checked"{/if}>
                    {/block}
                    {block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_vault_card_label"}
                        <label for="{$creditCard->getTypeId()}">
                            {$creditCard->getNumber()} {if $creditCard->getHolder()}({$creditCard->getHolder()}){/if}
                        </label>
                    {/block}
                </div>
            {/block}
        {/foreach}
    </div>
{/block}
