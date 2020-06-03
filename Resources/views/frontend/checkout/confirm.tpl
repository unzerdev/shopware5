{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_information_wrapper"}
    {$smarty.block.parent}

    {if $heidelpayFrame}
        {include file="frontend/heidelpay/checkout/confirm.tpl"}
    {/if}
{/block}
