{extends file="parent:frontend/account/payment.tpl"}

{block name="frontend_account_payment_content"}
    {if $unzerPaymentDeviceRemoved}
        {block name="frontend_account_payment_content_unzer_payment_message"}
            {include file="frontend/_includes/messages.tpl" type="success" content="{s name='message/deviceRemoved'}{/s}"}
        {/block}
    {/if}

    {$smarty.block.parent}

    {if $unzerPaymentVault}
        {include file="frontend/unzer_payment/account/payment_device_vault.tpl"}
    {/if}
{/block}
