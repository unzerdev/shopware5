{extends file="parent:frontend/account/payment.tpl"}

{block name="frontend_account_payment_content"}
    {if $heidelpayDeviceRemoved}
        {block name="frontend_account_payment_content_heidelpay_message"}
            {include file="frontend/_includes/messages.tpl" type="success" content="{s name='message/deviceRemoved'}{/s}"}
        {/block}
    {/if}

    {$smarty.block.parent}

    {if $heidelpayVault}
        {include file="frontend/heidelpay/account/vault.tpl"}
    {/if}
{/block}
