{extends file="parent:frontend/checkout/change_payment.tpl"}
{block name='frontend_checkout_payment_headline'}
    {$smarty.block.parent}
    <script type="module" src="https://static-v2.unzer.com/v2/ui-components/index.js" ></script>
{/block}
{block name='frontend_checkout_payment_fieldset_description'}
    {$smarty.block.parent}
    {if $payment_mean.name == 'unzerPaymentOpenBanking'}
        <unzer-payment
                publicKey="{config name="public_key" namespace="unzer_payment"}"
                locale="{$unzerPaymentLocale}">
            <unzer-open-banking></unzer-open-banking>
        </unzer-payment>
    {/if}
{/block}