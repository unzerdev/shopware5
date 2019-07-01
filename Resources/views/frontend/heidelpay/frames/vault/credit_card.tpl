{block name="frontend_checkout_confirm_heidelpay_frames_credit_card"}
    <div class="heidelpay--credit-card-vault">
        <p>Wählen Sie eine gespeicherte Kreditkarte oder füllen Sie das Formular aus!</p>
        {foreach $heidelpayVault['0'] as $creditCard}
            <div class="heidelpay--credit-card-vault-item">
                <input type="radio" id="{$creditCard->getTypeId()}" name="cardSelection"{if $creditCard@first} checked="checked"{/if}>

                <label for="{$creditCard->getTypeId()}">
                    {$creditCard->getNumber()} ({if $creditCard->getHolder()}{$creditCard->getHolder()} - {/if}{$creditCard->getExpiryDate()})
                </label>
            </div>
        {/foreach}
    </div>
{/block}
